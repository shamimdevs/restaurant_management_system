import { useState, useCallback, useEffect, useRef } from "react";
import { useDispatch, useSelector } from "react-redux";
import {
    addItem,
    removeItem,
    updateQuantity,
    clearCart,
    selectCartItems,
    selectCartSubtotal,
    selectCartCount,
    setOrderType,
    setCustomer,
    applyCoupon,
    removeCoupon,
    setLoyaltyPoints,
} from "@/store/cartSlice";
import { notify } from "@/store/notificationSlice";
import api from "@/lib/api";
import { formatCurrency, debounce } from "@/lib/utils";
import {
    Search,
    ShoppingCart,
    Plus,
    Minus,
    Trash2,
    X,
    Printer,
    CreditCard,
    Banknote,
    Smartphone,
    Tag,
    Users,
    RotateCcw,
    Gift,
    ChevronDown,
    CheckCircle2,
} from "lucide-react";
import Button from "@/Components/UI/Button";
import Modal from "@/Components/UI/Modal";
import { cn, formatCurrency as fc } from "@/lib/utils";

const ORDER_TYPES = [
    { value: "dine_in", label: "Dine In" },
    { value: "takeaway", label: "Takeaway" },
    { value: "delivery", label: "Delivery" },
];

const PAYMENT_METHODS = [
    { value: "cash", label: "Cash", icon: Banknote, color: "emerald" },
    { value: "card", label: "Card", icon: CreditCard, color: "blue" },
    { value: "bkash", label: "bKash", icon: Smartphone, color: "pink" },
    { value: "nagad", label: "Nagad", icon: Smartphone, color: "orange" },
    { value: "rocket", label: "Rocket", icon: Smartphone, color: "purple" },
];

const PM_STYLES = {
    emerald: {
        bg: "bg-emerald-50",
        border: "border-emerald-500",
        text: "text-emerald-700",
        icon: "text-emerald-600",
    },
    blue: {
        bg: "bg-blue-50",
        border: "border-blue-500",
        text: "text-blue-700",
        icon: "text-blue-600",
    },
    pink: {
        bg: "bg-pink-50",
        border: "border-pink-500",
        text: "text-pink-700",
        icon: "text-pink-600",
    },
    orange: {
        bg: "bg-orange-50",
        border: "border-orange-500",
        text: "text-orange-700",
        icon: "text-orange-600",
    },
    purple: {
        bg: "bg-purple-50",
        border: "border-purple-500",
        text: "text-purple-700",
        icon: "text-purple-600",
    },
};

export default function PosIndex({
    categories: initialCategories,
    tables,
    todayOrders,
}) {
    const dispatch = useDispatch();
    const cartItems = useSelector(selectCartItems);
    const subtotal = useSelector(selectCartSubtotal);
    const cartCount = useSelector(selectCartCount);
    const cart = useSelector((s) => s.cart);

    const [categories, setCategories] = useState(initialCategories || []);
    const [activeCategory, setActiveCategory] = useState(
        initialCategories?.[0]?.id || null,
    );
    const [items, setItems] = useState([]);
    const [loadingItems, setLoadingItems] = useState(false);
    const [search, setSearch] = useState("");

    const [orderType, setOrderTypeLocal] = useState("dine_in");
    const [selectedTable, setSelectedTable] = useState(null);
    const [customerPhone, setCustomerPhone] = useState("");
    const [customer, setCustomerData] = useState(null);

    const [couponInput, setCouponInput] = useState("");
    const [couponLoading, setCouponLoading] = useState(false);

    const [payModal, setPayModal] = useState(false);
    const [payments, setPayments] = useState([{ method: "cash", amount: "" }]);
    const [placingOrder, setPlacingOrder] = useState(false);
    const [lastOrder, setLastOrder] = useState(null);

    const [modifierModal, setModifierModal] = useState(null);
    const searchRef = useRef(null);

    // ─── Totals ───────────────────────────────────────────────────────────────
    const TAX_RATE = 0.05;
    const SERVICE_CHARGE = 0.1;
    const discountAmt = cart.couponDiscount || 0;
    const taxableAmount = subtotal - discountAmt;
    const vatAmount = parseFloat((taxableAmount * TAX_RATE).toFixed(2));
    const scAmount = parseFloat((taxableAmount * SERVICE_CHARGE).toFixed(2));
    const loyaltyDiscount = parseFloat(
        ((cart.loyaltyPointsToRedeem || 0) / 10).toFixed(2),
    );
    const total = Math.max(
        0,
        taxableAmount + vatAmount + scAmount - loyaltyDiscount,
    );
    const amountPaid = payments.reduce(
        (s, p) => s + parseFloat(p.amount || 0),
        0,
    );
    const change = Math.max(0, amountPaid - total);
    const balanceDue = Math.max(0, total - amountPaid);

    // ─── API helpers ──────────────────────────────────────────────────────────
    const loadItems = useCallback(
        async (categoryId, searchTerm = "") => {
            setLoadingItems(true);
            try {
                const params = {};
                if (searchTerm) params.search = searchTerm;
                else if (categoryId) params.category_id = categoryId;
                const { data } = await api.get("/pos/items", { params });
                setItems(Array.isArray(data) ? data : data.data || []);
            } catch {
                dispatch(notify("Failed to load menu items", "error"));
            } finally {
                setLoadingItems(false);
            }
        },
        [dispatch],
    );

    useEffect(() => {
        if (activeCategory) loadItems(activeCategory);
    }, [activeCategory]);

    const debouncedSearch = useCallback(
        debounce((q) => {
            if (q.length >= 2) loadItems(null, q);
            else if (activeCategory) loadItems(activeCategory);
        }, 300),
        [activeCategory],
    );

    const handleSearch = (e) => {
        setSearch(e.target.value);
        debouncedSearch(e.target.value);
    };

    const handleAddItem = (item) => {
        if (item.modifier_groups?.length > 0) {
            setModifierModal(item);
        } else {
            dispatch(addItem({ menuItem: item }));
            dispatch(notify(`${item.name} added`, "success", 1500));
        }
    };

    const lookupCustomer = async () => {
        if (!customerPhone) return;
        try {
            const { data } = await api.get("/customers/search", {
                params: { phone: customerPhone },
            });
            setCustomerData(data);
            dispatch(setCustomer(data.id));
            dispatch(notify(`Customer: ${data.name}`, "success"));
        } catch {
            dispatch(notify("Customer not found", "warning"));
        }
    };

    const applyCouponCode = async () => {
        if (!couponInput) return;
        setCouponLoading(true);
        try {
            const { data } = await api.post("/pos/apply-coupon", {
                code: couponInput,
                subtotal,
            });
            dispatch(
                applyCoupon({ code: couponInput, discount: data.discount }),
            );
            dispatch(
                notify(
                    `Coupon applied! -${formatCurrency(data.discount)}`,
                    "success",
                ),
            );
        } catch (err) {
            dispatch(
                notify(
                    err.response?.data?.message || "Invalid coupon",
                    "error",
                ),
            );
        } finally {
            setCouponLoading(false);
        }
    };

    const handlePlaceOrder = async () => {
        if (cartItems.length === 0)
            return dispatch(notify("Cart is empty", "warning"));
        setPlacingOrder(true);
        try {
            const { data } = await api.post("/pos/orders", {
                order_type: orderType,
                table_session_id: selectedTable?.session?.id || null,
                customer_id: customer?.id || null,
                coupon_code: cart.couponCode,
                loyalty_redeem_points: cart.loyaltyPointsToRedeem || 0,
                notes: cart.notes,
                items: cartItems.map((i) => ({
                    menu_item_id: i.menuItemId,
                    variant_id: i.variantId,
                    quantity: i.quantity,
                    unit_price: i.unitPrice,
                    notes: i.notes,
                    modifiers: i.modifiers.map((m) => ({
                        modifier_id: m.id,
                        price: m.price,
                    })),
                })),
                payments: payments.map((p) => ({
                    method: p.method,
                    amount: parseFloat(p.amount),
                })),
            });
            setLastOrder(data.order);
            dispatch(clearCart());
            setPayModal(false);
            setCustomerData(null);
            setCustomerPhone("");
            setPayments([{ method: "cash", amount: "" }]);
            dispatch(
                notify(`Order ${data.order.order_number} placed!`, "success"),
            );
        } catch (err) {
            dispatch(
                notify(err.response?.data?.message || "Order failed", "error"),
            );
        } finally {
            setPlacingOrder(false);
        }
    };

    const openPayModal = () => {
        setPayments([{ method: "cash", amount: total.toFixed(2) }]);
        setPayModal(true);
    };

    const setPaymentMethod = (method) => {
        setPayments([{ method, amount: payments[0]?.amount || "" }]);
    };

    const setPaymentAmount = (value) => {
        setPayments([{ ...payments[0], amount: value }]);
    };

    const setQuickAmount = (amt) => {
        setPayments([{ ...payments[0], amount: amt.toFixed(2) }]);
    };

    // Quick-cash buttons rounded up to nearest 50/100
    const quickAmounts = (() => {
        const base = Math.ceil(total / 50) * 50;
        return [...new Set([total, base, base + 50, base + 100])]
            .filter((a) => a >= total)
            .slice(0, 4);
    })();

    // ─── Render ───────────────────────────────────────────────────────────────
    return (
        <div className="flex h-screen bg-gray-100 overflow-hidden  p-2">
            {/* ── LEFT: Category sidebar + Items grid ── */}
            <div className="flex flex-col flex-1 min-w-0">
                {/* Top bar */}
                <div className="bg-white border-b border-gray-200 px-4 py-2.5 flex items-center gap-3 shadow-sm">
                    {/* Search */}
                    <div className="relative flex-1 max-w-xs">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                        <input
                            ref={searchRef}
                            value={search}
                            onChange={handleSearch}
                            placeholder="Search items, SKU, barcode…"
                            className="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-400 transition"
                        />
                    </div>

                    {/* Order type */}
                    <div className="flex bg-gray-100 rounded-lg p-0.5 gap-0.5">
                        {ORDER_TYPES.map((t) => (
                            <button
                                key={t.value}
                                onClick={() => {
                                    setOrderTypeLocal(t.value);
                                    dispatch(setOrderType(t.value));
                                }}
                                className={cn(
                                    "px-3 py-1.5 text-xs font-medium rounded-md transition-all",
                                    orderType === t.value
                                        ? "bg-white text-violet-700 shadow-sm"
                                        : "text-gray-500 hover:text-gray-700",
                                )}
                            >
                                {t.label}
                            </button>
                        ))}
                    </div>

                    {/* Customer lookup */}
                    <div className="flex items-center gap-2">
                        <div className="relative">
                            <input
                                value={customerPhone}
                                onChange={(e) =>
                                    setCustomerPhone(e.target.value)
                                }
                                onKeyDown={(e) =>
                                    e.key === "Enter" && lookupCustomer()
                                }
                                placeholder="Phone number"
                                className="w-36 text-sm border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-400 transition"
                            />
                        </div>
                        <button
                            onClick={lookupCustomer}
                            className="p-2 bg-violet-100 text-violet-700 rounded-lg hover:bg-violet-200 transition"
                        >
                            <Users className="w-4 h-4" />
                        </button>
                        {customer && (
                            <div className="flex items-center gap-1.5 bg-emerald-50 text-emerald-700 text-xs font-medium px-3 py-1.5 rounded-lg border border-emerald-200">
                                <CheckCircle2 className="w-3.5 h-3.5" />
                                {customer.name} · {customer.total_points} pts
                            </div>
                        )}
                    </div>
                </div>

                <div className="flex flex-1 min-h-0">
                    {/* Category sidebar */}
                    <div className="w-28 bg-white border-r border-gray-200 overflow-y-auto flex-shrink-0">
                        {categories.map((cat) => (
                            <button
                                key={cat.id}
                                onClick={() => {
                                    setActiveCategory(cat.id);
                                    setSearch("");
                                }}
                                className={cn(
                                    "w-full px-2 py-3.5 text-xs font-medium text-center border-b border-gray-100 transition-all relative",
                                    activeCategory === cat.id
                                        ? "bg-violet-50 text-violet-700"
                                        : "text-gray-500 hover:bg-gray-50 hover:text-gray-700",
                                )}
                            >
                                {activeCategory === cat.id && (
                                    <span className="absolute left-0 top-1/4 bottom-1/4 w-0.5 bg-violet-600 rounded-r" />
                                )}
                                {cat.name}
                                {cat.menu_items_count > 0 && (
                                    <span className="block text-[10px] text-gray-400 mt-0.5">
                                        {cat.menu_items_count} items
                                    </span>
                                )}
                            </button>
                        ))}
                    </div>

                    {/* Items grid */}
                    <div className="flex-1 overflow-y-auto p-3">
                        {loadingItems ? (
                            <div className="grid grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-2.5">
                                {Array.from({ length: 12 }).map((_, i) => (
                                    <div
                                        key={i}
                                        className="h-32 bg-gray-200 rounded-xl animate-pulse"
                                    />
                                ))}
                            </div>
                        ) : (
                            <div className="grid grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-2.5">
                                {items.map((item) => (
                                    <button
                                        key={item.id}
                                        onClick={() => handleAddItem(item)}
                                        disabled={!item.is_available}
                                        className={cn(
                                            "relative flex flex-col items-center p-3 rounded-xl border text-center transition-all group",
                                            item.is_available
                                                ? "bg-white border-gray-200 hover:border-violet-400 hover:shadow-md hover:shadow-violet-100 active:scale-95 cursor-pointer"
                                                : "bg-gray-50 border-dashed border-gray-200 opacity-50 cursor-not-allowed",
                                        )}
                                    >
                                        {item.image ? (
                                            <img
                                                src={`/storage/${item.image}`}
                                                alt={item.name}
                                                className="w-14 h-14 object-cover rounded-lg mb-2"
                                            />
                                        ) : (
                                            <div className="w-14 h-14 bg-gradient-to-br from-violet-100 to-indigo-100 rounded-lg mb-2 flex items-center justify-center text-violet-600 text-2xl font-bold">
                                                {item.name[0]}
                                            </div>
                                        )}
                                        <p className="text-xs font-semibold text-gray-800 leading-tight line-clamp-2 mb-1">
                                            {item.name}
                                        </p>
                                        <p className="text-sm font-bold text-violet-700">
                                            {formatCurrency(item.price)}
                                        </p>

                                        {item.is_available && (
                                            <div className="absolute inset-0 rounded-xl bg-violet-600/5 opacity-0 group-hover:opacity-100 transition-opacity" />
                                        )}
                                        {!item.is_available && (
                                            <span className="absolute top-2 right-2 text-[9px] bg-red-100 text-red-600 px-1.5 py-0.5 rounded font-medium">
                                                Sold out
                                            </span>
                                        )}
                                    </button>
                                ))}

                                {items.length === 0 && !loadingItems && (
                                    <div className="col-span-full text-center py-20 text-gray-400">
                                        <ShoppingCart className="w-10 h-10 mx-auto mb-3 opacity-20" />
                                        <p className="text-sm">
                                            No items found
                                        </p>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* ── RIGHT: Cart panel ── */}
            <div className="w-80 xl:w-[360px] bg-white border-l border-gray-200 flex flex-col flex-shrink-0 shadow-xl">
                {/* Cart header */}
                <div className="px-4 py-3 flex items-center justify-between bg-gradient-to-r from-violet-600 to-indigo-600">
                    <div className="flex items-center gap-2 text-white">
                        <ShoppingCart className="w-4 h-4" />
                        <span className="font-semibold text-sm">
                            Current Order
                        </span>
                        {cartCount > 0 && (
                            <span className="bg-white/20 text-white text-xs px-2 py-0.5 rounded-full font-bold">
                                {cartCount}
                            </span>
                        )}
                    </div>
                    <button
                        onClick={() => dispatch(clearCart())}
                        className="text-white/60 hover:text-white transition-colors p-1 rounded hover:bg-white/10"
                    >
                        <RotateCcw className="w-3.5 h-3.5" />
                    </button>
                </div>

                {/* Cart items */}
                <div className="flex-1 overflow-y-auto px-3 py-2 space-y-1.5">
                    {cartItems.length === 0 ? (
                        <div className="flex flex-col items-center justify-center h-full text-gray-300 py-16">
                            <ShoppingCart className="w-14 h-14 opacity-30 mb-3" />
                            <p className="text-sm font-medium">Cart is empty</p>
                            <p className="text-xs mt-1 opacity-70">
                                Tap items to add them
                            </p>
                        </div>
                    ) : (
                        cartItems.map((item) => (
                            <div
                                key={item.key}
                                className="bg-gray-50 rounded-xl px-3 py-2.5 border border-gray-100"
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-semibold text-gray-900 truncate">
                                            {item.name}
                                        </p>
                                        {item.variantName && (
                                            <p className="text-xs text-gray-400 mt-0.5">
                                                {item.variantName}
                                            </p>
                                        )}
                                        {item.modifiers.length > 0 && (
                                            <p className="text-xs text-violet-500 mt-0.5 truncate">
                                                +{" "}
                                                {item.modifiers
                                                    .map((m) => m.name)
                                                    .join(", ")}
                                            </p>
                                        )}
                                    </div>
                                    <button
                                        onClick={() =>
                                            dispatch(removeItem(item.key))
                                        }
                                        className="text-gray-300 hover:text-red-400 transition-colors flex-shrink-0 p-0.5 rounded"
                                    >
                                        <Trash2 className="w-3.5 h-3.5" />
                                    </button>
                                </div>

                                <div className="flex items-center justify-between mt-2.5">
                                    <div className="flex items-center gap-1">
                                        <button
                                            onClick={() =>
                                                dispatch(
                                                    updateQuantity({
                                                        key: item.key,
                                                        quantity:
                                                            item.quantity - 1,
                                                    }),
                                                )
                                            }
                                            className="w-6 h-6 rounded-md bg-white border border-gray-200 hover:border-gray-300 flex items-center justify-center transition-colors shadow-sm"
                                        >
                                            <Minus className="w-3 h-3 text-gray-600" />
                                        </button>
                                        <span className="w-8 text-center text-sm font-bold text-gray-800">
                                            {item.quantity}
                                        </span>
                                        <button
                                            onClick={() =>
                                                dispatch(
                                                    updateQuantity({
                                                        key: item.key,
                                                        quantity:
                                                            item.quantity + 1,
                                                    }),
                                                )
                                            }
                                            className="w-6 h-6 rounded-md bg-violet-600 hover:bg-violet-700 flex items-center justify-center transition-colors shadow-sm"
                                        >
                                            <Plus className="w-3 h-3 text-white" />
                                        </button>
                                    </div>
                                    <p className="text-sm font-bold text-gray-900">
                                        {formatCurrency(
                                            item.unitPrice * item.quantity,
                                        )}
                                    </p>
                                </div>
                            </div>
                        ))
                    )}
                </div>

                {/* Coupon + Loyalty */}
                {cartItems.length > 0 && (
                    <div className="px-3 pt-2 pb-1 border-t border-gray-100 space-y-1.5">
                        {cart.couponCode ? (
                            <div className="flex items-center justify-between bg-emerald-50 border border-emerald-200 px-3 py-2 rounded-lg">
                                <span className="text-xs text-emerald-700 font-semibold flex items-center gap-1.5">
                                    <Tag className="w-3.5 h-3.5" />
                                    {cart.couponCode} — -
                                    {formatCurrency(cart.couponDiscount)}
                                </span>
                                <button
                                    onClick={() => dispatch(removeCoupon())}
                                    className="text-emerald-500 hover:text-red-400 transition-colors"
                                >
                                    <X className="w-4 h-4" />
                                </button>
                            </div>
                        ) : (
                            <div className="flex gap-1.5">
                                <input
                                    value={couponInput}
                                    onChange={(e) =>
                                        setCouponInput(
                                            e.target.value.toUpperCase(),
                                        )
                                    }
                                    onKeyDown={(e) =>
                                        e.key === "Enter" && applyCouponCode()
                                    }
                                    placeholder="Coupon code"
                                    className="flex-1 text-xs border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-400 transition"
                                />
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={applyCouponCode}
                                    loading={couponLoading}
                                    className="text-xs"
                                >
                                    Apply
                                </Button>
                            </div>
                        )}

                        {customer?.total_points > 0 && (
                            <div className="flex items-center justify-between bg-purple-50 border border-purple-100 px-3 py-2 rounded-lg">
                                <span className="text-xs text-purple-700 flex items-center gap-1.5">
                                    <Gift className="w-3.5 h-3.5" />
                                    {customer.total_points} pts ·{" "}
                                    {formatCurrency(customer.total_points / 10)}{" "}
                                    value
                                </span>
                                <button
                                    onClick={() =>
                                        dispatch(
                                            setLoyaltyPoints(
                                                cart.loyaltyPointsToRedeem
                                                    ? 0
                                                    : Math.min(
                                                          customer.total_points,
                                                          Math.floor(
                                                              subtotal / 10,
                                                          ) * 10,
                                                      ),
                                            ),
                                        )
                                    }
                                    className="text-xs font-semibold text-purple-700 hover:underline"
                                >
                                    {cart.loyaltyPointsToRedeem
                                        ? "Remove"
                                        : "Redeem"}
                                </button>
                            </div>
                        )}
                    </div>
                )}

                {/* Totals */}
                {cartItems.length > 0 && (
                    <div className="border-t border-gray-100 px-4 py-3 space-y-1 text-sm bg-gray-50/50">
                        <div className="flex justify-between text-gray-500">
                            <span>Subtotal</span>
                            <span className="font-medium text-gray-700">
                                {formatCurrency(subtotal)}
                            </span>
                        </div>
                        {discountAmt > 0 && (
                            <div className="flex justify-between text-emerald-600 text-xs">
                                <span>Coupon discount</span>
                                <span>-{formatCurrency(discountAmt)}</span>
                            </div>
                        )}
                        <div className="flex justify-between text-gray-500 text-xs">
                            <span>VAT (5%)</span>
                            <span>{formatCurrency(vatAmount)}</span>
                        </div>
                        <div className="flex justify-between text-gray-500 text-xs">
                            <span>Service charge (10%)</span>
                            <span>{formatCurrency(scAmount)}</span>
                        </div>
                        {loyaltyDiscount > 0 && (
                            <div className="flex justify-between text-purple-600 text-xs">
                                <span>Loyalty points</span>
                                <span>-{formatCurrency(loyaltyDiscount)}</span>
                            </div>
                        )}
                        <div className="flex justify-between text-gray-900 font-bold text-base pt-2 border-t border-gray-200 mt-1">
                            <span>Total</span>
                            <span className="text-violet-700">
                                {formatCurrency(total)}
                            </span>
                        </div>
                    </div>
                )}

                {/* Charge button */}
                <div className="p-3 border-t border-gray-100">
                    <button
                        disabled={cartItems.length === 0}
                        onClick={openPayModal}
                        className={cn(
                            "w-full flex items-center justify-center gap-2 py-3.5 rounded-xl font-semibold text-sm transition-all",
                            cartItems.length > 0
                                ? "bg-violet-600 hover:bg-violet-700 active:scale-[.98] text-white shadow-md shadow-violet-200"
                                : "bg-gray-100 text-gray-300 cursor-not-allowed",
                        )}
                    >
                        <CreditCard className="w-4 h-4" />
                        {cartItems.length > 0
                            ? `Charge ${formatCurrency(total)}`
                            : "Cart is empty"}
                    </button>
                </div>
            </div>

            {/* ── Payment Modal ── */}
            <Modal
                open={payModal}
                onClose={() => setPayModal(false)}
                title="Process Payment"
                size="md"
            >
                <div className="space-y-5">
                    {/* Order summary */}
                    <div className="bg-gray-50 border border-gray-100 rounded-xl p-4 space-y-1.5 text-sm">
                        <div className="flex justify-between text-gray-500">
                            <span>Subtotal</span>
                            <span>{formatCurrency(subtotal)}</span>
                        </div>
                        {discountAmt > 0 && (
                            <div className="flex justify-between text-emerald-600 text-xs">
                                <span>Discount</span>
                                <span>-{formatCurrency(discountAmt)}</span>
                            </div>
                        )}
                        <div className="flex justify-between text-gray-500 text-xs">
                            <span>VAT 5%</span>
                            <span>{formatCurrency(vatAmount)}</span>
                        </div>
                        <div className="flex justify-between text-gray-500 text-xs">
                            <span>Service 10%</span>
                            <span>{formatCurrency(scAmount)}</span>
                        </div>
                        {loyaltyDiscount > 0 && (
                            <div className="flex justify-between text-purple-600 text-xs">
                                <span>Loyalty discount</span>
                                <span>-{formatCurrency(loyaltyDiscount)}</span>
                            </div>
                        )}
                        <div className="flex justify-between font-bold text-base text-gray-900 pt-2 border-t border-gray-200">
                            <span>Total Due</span>
                            <span className="text-violet-700">
                                {formatCurrency(total)}
                            </span>
                        </div>
                    </div>

                    {/* Payment method */}
                    <div>
                        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                            Payment method
                        </p>
                        <div className="grid grid-cols-5 gap-2">
                            {PAYMENT_METHODS.map((pm) => {
                                const s = PM_STYLES[pm.color];
                                const active = payments[0]?.method === pm.value;
                                return (
                                    <button
                                        key={pm.value}
                                        onClick={() =>
                                            setPaymentMethod(pm.value)
                                        }
                                        className={cn(
                                            "flex flex-col items-center py-3 px-1 rounded-xl border-2 text-xs font-semibold transition-all",
                                            active
                                                ? `${s.bg} ${s.border} ${s.text}`
                                                : "border-gray-200 text-gray-500 hover:border-gray-300 bg-white",
                                        )}
                                    >
                                        <pm.icon
                                            className={cn(
                                                "w-5 h-5 mb-1",
                                                active
                                                    ? s.icon
                                                    : "text-gray-400",
                                            )}
                                        />
                                        {pm.label}
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    {/* Amount input */}
                    <div>
                        <div className="flex items-center justify-between mb-2">
                            <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                Amount received
                            </p>
                            <button
                                onClick={() =>
                                    setPaymentAmount(total.toFixed(2))
                                }
                                className="text-xs font-semibold text-violet-600 hover:underline"
                            >
                                Exact amount
                            </button>
                        </div>

                        {/* Quick amount buttons */}
                        <div className="grid grid-cols-4 gap-1.5 mb-2">
                            {quickAmounts.map((amt) => (
                                <button
                                    key={amt}
                                    onClick={() => setQuickAmount(amt)}
                                    className={cn(
                                        "py-2 rounded-lg text-xs font-semibold border transition-all",
                                        parseFloat(payments[0]?.amount) === amt
                                            ? "bg-violet-600 text-white border-violet-600"
                                            : "bg-gray-50 text-gray-700 border-gray-200 hover:border-violet-300 hover:text-violet-700",
                                    )}
                                >
                                    {formatCurrency(amt)}
                                </button>
                            ))}
                        </div>

                        <input
                            type="number"
                            value={payments[0]?.amount || ""}
                            onChange={(e) => setPaymentAmount(e.target.value)}
                            className="w-full text-3xl font-bold text-center border-2 border-gray-200 rounded-xl py-3.5 focus:outline-none focus:border-violet-500 bg-gray-50 focus:bg-white transition text-gray-900"
                            placeholder="0.00"
                            autoFocus
                        />
                    </div>

                    {/* Change / balance */}
                    {amountPaid > 0 && (
                        <div
                            className={cn(
                                "flex justify-between items-center rounded-xl px-4 py-3.5 font-bold text-lg border",
                                balanceDue > 0
                                    ? "bg-red-50 text-red-700 border-red-200"
                                    : "bg-emerald-50 text-emerald-700 border-emerald-200",
                            )}
                        >
                            <span className="text-sm font-semibold">
                                {balanceDue > 0 ? "Balance due" : "Change"}
                            </span>
                            <span>
                                {formatCurrency(
                                    balanceDue > 0 ? balanceDue : change,
                                )}
                            </span>
                        </div>
                    )}

                    {/* Complete button */}
                    <Button
                        className="w-full"
                        size="lg"
                        onClick={handlePlaceOrder}
                        loading={placingOrder}
                        disabled={amountPaid < total}
                    >
                        <Printer className="w-4 h-4" />
                        Complete &amp; Print Receipt
                    </Button>
                </div>
            </Modal>
        </div>
    );
}
