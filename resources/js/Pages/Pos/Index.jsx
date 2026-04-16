import { useState, useCallback, useEffect, useRef } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import {
    addItem, removeItem, updateQuantity, clearCart,
    selectCartItems, selectCartSubtotal, selectCartCount,
    setOrderType, setCustomer, applyCoupon, removeCoupon,
    setLoyaltyPoints,
} from '@/store/cartSlice';
import { notify } from '@/store/notificationSlice';
import api from '@/lib/api';
import { formatCurrency, debounce } from '@/lib/utils';
import {
    Search, ShoppingCart, Plus, Minus, Trash2, X, Printer,
    CreditCard, Banknote, Smartphone, Tag, Users, ChevronRight,
    RotateCcw, SplitSquareVertical, Percent, Gift,
} from 'lucide-react';
import Button from '@/Components/UI/Button';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import { cn, formatCurrency as fc } from '@/lib/utils';

const ORDER_TYPES = [
    { value: 'dine_in', label: 'Dine In' },
    { value: 'takeaway', label: 'Takeaway' },
    { value: 'delivery', label: 'Delivery' },
];

const PAYMENT_METHODS = [
    { value: 'cash', label: 'Cash', icon: Banknote, color: 'green' },
    { value: 'card', label: 'Card', icon: CreditCard, color: 'blue' },
    { value: 'bkash', label: 'bKash', icon: Smartphone, color: 'pink' },
    { value: 'nagad', label: 'Nagad', icon: Smartphone, color: 'orange' },
    { value: 'rocket', label: 'Rocket', icon: Smartphone, color: 'purple' },
];

export default function PosIndex({ categories: initialCategories, tables, todayOrders }) {
    const dispatch = useDispatch();
    const cartItems = useSelector(selectCartItems);
    const subtotal = useSelector(selectCartSubtotal);
    const cartCount = useSelector(selectCartCount);
    const cart = useSelector(s => s.cart);

    const [categories, setCategories] = useState(initialCategories || []);
    const [activeCategory, setActiveCategory] = useState(initialCategories?.[0]?.id || null);
    const [items, setItems] = useState([]);
    const [loadingItems, setLoadingItems] = useState(false);
    const [search, setSearch] = useState('');

    const [orderType, setOrderTypeLocal] = useState('dine_in');
    const [selectedTable, setSelectedTable] = useState(null);
    const [customerPhone, setCustomerPhone] = useState('');
    const [customer, setCustomerData] = useState(null);

    const [couponInput, setCouponInput] = useState('');
    const [couponLoading, setCouponLoading] = useState(false);

    const [payModal, setPayModal] = useState(false);
    const [payments, setPayments] = useState([{ method: 'cash', amount: '' }]);
    const [placingOrder, setPlacingOrder] = useState(false);
    const [lastOrder, setLastOrder] = useState(null);

    const [modifierModal, setModifierModal] = useState(null);

    const searchRef = useRef(null);

    // Calculate totals
    const taxRate = 0.05; // 5% restaurant VAT (SRO)
    const serviceCharge = 0.1; // 10%
    const discountAmt = cart.couponDiscount || 0;
    const taxableAmount = subtotal - discountAmt;
    const vatAmount = parseFloat((taxableAmount * taxRate).toFixed(2));
    const scAmount = parseFloat((taxableAmount * serviceCharge).toFixed(2));
    const loyaltyDiscount = parseFloat(((cart.loyaltyPointsToRedeem || 0) / 10).toFixed(2)); // 10 pts = 1 BDT
    const total = Math.max(0, taxableAmount + vatAmount + scAmount - loyaltyDiscount);
    const amountPaid = payments.reduce((s, p) => s + parseFloat(p.amount || 0), 0);
    const change = Math.max(0, amountPaid - total);

    // Load items for category
    const loadItems = useCallback(async (categoryId, searchTerm = '') => {
        setLoadingItems(true);
        try {
            const { data } = await api.get('/pos/items', {
                params: { category_id: categoryId, search: searchTerm, per_page: 50 },
            });
            setItems(data.data || data);
        } catch {
            dispatch(notify('Failed to load menu items', 'error'));
        } finally {
            setLoadingItems(false);
        }
    }, [dispatch]);

    useEffect(() => {
        if (activeCategory) loadItems(activeCategory);
    }, [activeCategory]);

    const debouncedSearch = useCallback(debounce((q) => {
        if (q.length >= 2) loadItems(null, q);
        else if (activeCategory) loadItems(activeCategory);
    }, 300), [activeCategory]);

    const handleSearch = (e) => {
        setSearch(e.target.value);
        debouncedSearch(e.target.value);
    };

    const handleAddItem = (item) => {
        if (item.modifier_groups?.length > 0) {
            setModifierModal(item);
        } else {
            dispatch(addItem({ menuItem: item }));
            dispatch(notify(`${item.name} added`, 'success', 1500));
        }
    };

    const lookupCustomer = async () => {
        if (!customerPhone) return;
        try {
            const { data } = await api.get('/customers/search', { params: { phone: customerPhone } });
            setCustomerData(data);
            dispatch(setCustomer(data.id));
            dispatch(notify(`Customer: ${data.name}`, 'success'));
        } catch {
            dispatch(notify('Customer not found', 'warning'));
        }
    };

    const applyCouponCode = async () => {
        if (!couponInput) return;
        setCouponLoading(true);
        try {
            const { data } = await api.post('/pos/apply-coupon', {
                code: couponInput,
                subtotal,
            });
            dispatch(applyCoupon({ code: couponInput, discount: data.discount }));
            dispatch(notify(`Coupon applied! -${formatCurrency(data.discount)}`, 'success'));
        } catch (err) {
            dispatch(notify(err.response?.data?.message || 'Invalid coupon', 'error'));
        } finally {
            setCouponLoading(false);
        }
    };

    const handlePlaceOrder = async () => {
        if (cartItems.length === 0) return dispatch(notify('Cart is empty', 'warning'));

        setPlacingOrder(true);
        try {
            const { data } = await api.post('/pos/order', {
                order_type: orderType,
                table_session_id: selectedTable?.session?.id || null,
                customer_id: customer?.id || null,
                coupon_code: cart.couponCode,
                loyalty_points_redeemed: cart.loyaltyPointsToRedeem || 0,
                notes: cart.notes,
                items: cartItems.map(i => ({
                    menu_item_id: i.menuItemId,
                    variant_id: i.variantId,
                    quantity: i.quantity,
                    unit_price: i.unitPrice,
                    notes: i.notes,
                    modifiers: i.modifiers.map(m => ({ modifier_id: m.id, price: m.price })),
                })),
                payments: payments.map(p => ({ method: p.method, amount: parseFloat(p.amount) })),
            });

            setLastOrder(data.order);
            dispatch(clearCart());
            setPayModal(false);
            setCustomerData(null);
            setCustomerPhone('');
            setPayments([{ method: 'cash', amount: '' }]);
            dispatch(notify(`Order ${data.order.order_number} placed!`, 'success'));
        } catch (err) {
            dispatch(notify(err.response?.data?.message || 'Order failed', 'error'));
        } finally {
            setPlacingOrder(false);
        }
    };

    const setPaymentAmount = (index, value) => {
        const next = [...payments];
        next[index] = { ...next[index], amount: value };
        setPayments(next);
    };

    const exactChange = () => {
        setPayments([{ method: payments[0]?.method || 'cash', amount: total.toFixed(2) }]);
    };

    return (
        <div className="flex h-screen bg-gray-100 overflow-hidden -m-6">
            {/* LEFT: Category + Items */}
            <div className="flex flex-col flex-1 min-w-0">
                {/* Header bar */}
                <div className="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3">
                    {/* Search */}
                    <div className="relative flex-1 max-w-sm">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input
                            ref={searchRef}
                            value={search}
                            onChange={handleSearch}
                            placeholder="Search items, SKU, barcode..."
                            className="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500"
                        />
                    </div>

                    {/* Order type tabs */}
                    <div className="flex bg-gray-100 rounded-lg p-0.5">
                        {ORDER_TYPES.map(t => (
                            <button
                                key={t.value}
                                onClick={() => { setOrderTypeLocal(t.value); dispatch(setOrderType(t.value)); }}
                                className={cn(
                                    'px-3 py-1.5 text-sm font-medium rounded-md transition-all',
                                    orderType === t.value
                                        ? 'bg-white text-violet-700 shadow-sm'
                                        : 'text-gray-500 hover:text-gray-700',
                                )}
                            >
                                {t.label}
                            </button>
                        ))}
                    </div>

                    {/* Customer */}
                    <div className="flex items-center gap-2">
                        <input
                            value={customerPhone}
                            onChange={e => setCustomerPhone(e.target.value)}
                            onKeyDown={e => e.key === 'Enter' && lookupCustomer()}
                            placeholder="Customer phone"
                            className="w-36 text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500"
                        />
                        <button onClick={lookupCustomer} className="p-2 bg-violet-100 text-violet-700 rounded-lg hover:bg-violet-200 transition-colors">
                            <Users className="w-4 h-4" />
                        </button>
                        {customer && (
                            <span className="text-sm font-medium text-green-700 bg-green-100 px-2 py-1 rounded-lg">
                                {customer.name} · {customer.total_points}pts
                            </span>
                        )}
                    </div>
                </div>

                <div className="flex flex-1 min-h-0">
                    {/* Category sidebar */}
                    <div className="w-32 bg-white border-r border-gray-200 overflow-y-auto flex-shrink-0">
                        {categories.map(cat => (
                            <button
                                key={cat.id}
                                onClick={() => { setActiveCategory(cat.id); setSearch(''); }}
                                className={cn(
                                    'w-full px-2 py-3 text-xs font-medium text-center border-b border-gray-100 transition-all',
                                    activeCategory === cat.id
                                        ? 'bg-violet-50 text-violet-700 border-l-2 border-l-violet-600'
                                        : 'text-gray-600 hover:bg-gray-50',
                                )}
                                style={{ borderLeftColor: activeCategory === cat.id ? cat.color || undefined : undefined }}
                            >
                                {cat.name}
                                {cat.menu_items_count > 0 && (
                                    <span className="block text-[10px] text-gray-400 mt-0.5">{cat.menu_items_count}</span>
                                )}
                            </button>
                        ))}
                    </div>

                    {/* Items grid */}
                    <div className="flex-1 overflow-y-auto p-3">
                        {loadingItems ? (
                            <div className="grid grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-2">
                                {Array.from({ length: 12 }).map((_, i) => (
                                    <div key={i} className="h-28 bg-gray-200 rounded-xl animate-pulse" />
                                ))}
                            </div>
                        ) : (
                            <div className="grid grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-2">
                                {items.map(item => (
                                    <button
                                        key={item.id}
                                        onClick={() => handleAddItem(item)}
                                        disabled={!item.is_available}
                                        className={cn(
                                            'relative flex flex-col items-center justify-center p-3 rounded-xl border-2 text-center transition-all',
                                            'hover:border-violet-400 hover:shadow-md active:scale-95',
                                            item.is_available
                                                ? 'bg-white border-gray-200 cursor-pointer'
                                                : 'bg-gray-50 border-dashed border-gray-200 opacity-50 cursor-not-allowed',
                                        )}
                                    >
                                        {item.image && (
                                            <img src={`/storage/${item.image}`} alt={item.name}
                                                className="w-12 h-12 object-cover rounded-lg mb-1.5" />
                                        )}
                                        {!item.image && (
                                            <div className="w-12 h-12 bg-gradient-to-br from-violet-100 to-indigo-100 rounded-lg mb-1.5 flex items-center justify-center text-violet-600 text-xl font-bold">
                                                {item.name[0]}
                                            </div>
                                        )}
                                        <p className="text-xs font-semibold text-gray-800 leading-tight line-clamp-2">{item.name}</p>
                                        <p className="text-sm font-bold text-violet-700 mt-1">{formatCurrency(item.price)}</p>
                                        {!item.is_available && (
                                            <span className="absolute top-1.5 right-1.5 text-[9px] bg-red-100 text-red-600 px-1 rounded">sold out</span>
                                        )}
                                    </button>
                                ))}
                                {items.length === 0 && !loadingItems && (
                                    <div className="col-span-full text-center py-16 text-gray-400">
                                        <ShoppingCart className="w-12 h-12 mx-auto mb-3 opacity-30" />
                                        <p>No items found</p>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* RIGHT: Cart panel */}
            <div className="w-80 xl:w-96 bg-white border-l border-gray-200 flex flex-col flex-shrink-0">
                {/* Cart header */}
                <div className="px-4 py-3 border-b border-gray-200 flex items-center justify-between bg-gradient-to-r from-violet-600 to-indigo-600">
                    <div className="flex items-center gap-2 text-white">
                        <ShoppingCart className="w-5 h-5" />
                        <span className="font-semibold">Cart</span>
                        {cartCount > 0 && (
                            <span className="bg-white/20 text-white text-xs px-2 py-0.5 rounded-full font-bold">
                                {cartCount}
                            </span>
                        )}
                    </div>
                    <button onClick={() => dispatch(clearCart())} className="text-white/70 hover:text-white transition-colors">
                        <RotateCcw className="w-4 h-4" />
                    </button>
                </div>

                {/* Cart items */}
                <div className="flex-1 overflow-y-auto px-3 py-2">
                    {cartItems.length === 0 ? (
                        <div className="flex flex-col items-center justify-center h-full text-gray-400 py-12">
                            <ShoppingCart className="w-16 h-16 opacity-20 mb-3" />
                            <p className="text-sm">Tap items to add to cart</p>
                        </div>
                    ) : (
                        <div className="space-y-1.5">
                            {cartItems.map(item => (
                                <div key={item.key} className="bg-gray-50 rounded-xl px-3 py-2.5">
                                    <div className="flex items-start justify-between gap-2">
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-medium text-gray-900 truncate">{item.name}</p>
                                            {item.variantName && <p className="text-xs text-gray-500">{item.variantName}</p>}
                                            {item.modifiers.length > 0 && (
                                                <p className="text-xs text-gray-500 truncate">
                                                    +{item.modifiers.map(m => m.name).join(', ')}
                                                </p>
                                            )}
                                        </div>
                                        <button
                                            onClick={() => dispatch(removeItem(item.key))}
                                            className="text-gray-300 hover:text-red-500 transition-colors flex-shrink-0"
                                        >
                                            <Trash2 className="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                    <div className="flex items-center justify-between mt-2">
                                        <div className="flex items-center gap-1">
                                            <button
                                                onClick={() => dispatch(updateQuantity({ key: item.key, quantity: item.quantity - 1 }))}
                                                className="w-6 h-6 rounded-md bg-gray-200 hover:bg-gray-300 flex items-center justify-center transition-colors"
                                            >
                                                <Minus className="w-3 h-3" />
                                            </button>
                                            <span className="w-8 text-center text-sm font-semibold">{item.quantity}</span>
                                            <button
                                                onClick={() => dispatch(updateQuantity({ key: item.key, quantity: item.quantity + 1 }))}
                                                className="w-6 h-6 rounded-md bg-violet-100 hover:bg-violet-200 text-violet-700 flex items-center justify-center transition-colors"
                                            >
                                                <Plus className="w-3 h-3" />
                                            </button>
                                        </div>
                                        <p className="text-sm font-bold text-gray-900">
                                            {formatCurrency(item.unitPrice * item.quantity)}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Coupon */}
                {cartItems.length > 0 && (
                    <div className="px-3 pb-2 border-t border-gray-100 pt-2">
                        {cart.couponCode ? (
                            <div className="flex items-center justify-between bg-green-50 px-3 py-2 rounded-lg">
                                <span className="text-sm text-green-700 font-medium flex items-center gap-1.5">
                                    <Tag className="w-3.5 h-3.5" />
                                    {cart.couponCode} · -{formatCurrency(cart.couponDiscount)}
                                </span>
                                <button onClick={() => dispatch(removeCoupon())} className="text-green-600 hover:text-red-500 transition-colors">
                                    <X className="w-4 h-4" />
                                </button>
                            </div>
                        ) : (
                            <div className="flex gap-1.5">
                                <input
                                    value={couponInput}
                                    onChange={e => setCouponInput(e.target.value.toUpperCase())}
                                    onKeyDown={e => e.key === 'Enter' && applyCouponCode()}
                                    placeholder="Coupon code"
                                    className="flex-1 text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-violet-500"
                                />
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={applyCouponCode}
                                    loading={couponLoading}
                                >
                                    Apply
                                </Button>
                            </div>
                        )}

                        {/* Loyalty points */}
                        {customer && customer.total_points > 0 && (
                            <div className="flex items-center justify-between mt-2 bg-purple-50 px-3 py-2 rounded-lg">
                                <span className="text-xs text-purple-700 flex items-center gap-1">
                                    <Gift className="w-3.5 h-3.5" />
                                    {customer.total_points} points · {formatCurrency(customer.total_points / 10)} value
                                </span>
                                <button
                                    onClick={() => dispatch(setLoyaltyPoints(
                                        cart.loyaltyPointsToRedeem ? 0 : Math.min(customer.total_points, Math.floor(subtotal / 10) * 10)
                                    ))}
                                    className="text-xs font-medium text-purple-700 hover:underline"
                                >
                                    {cart.loyaltyPointsToRedeem ? 'Remove' : 'Redeem'}
                                </button>
                            </div>
                        )}
                    </div>
                )}

                {/* Totals */}
                {cartItems.length > 0 && (
                    <div className="border-t border-gray-200 px-4 py-3 space-y-1.5 text-sm">
                        <div className="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span className="font-medium">{formatCurrency(subtotal)}</span>
                        </div>
                        {discountAmt > 0 && (
                            <div className="flex justify-between text-green-600">
                                <span>Discount</span>
                                <span>-{formatCurrency(discountAmt)}</span>
                            </div>
                        )}
                        <div className="flex justify-between text-gray-600">
                            <span>VAT (5%)</span>
                            <span>{formatCurrency(vatAmount)}</span>
                        </div>
                        <div className="flex justify-between text-gray-600">
                            <span>Service Charge (10%)</span>
                            <span>{formatCurrency(scAmount)}</span>
                        </div>
                        {loyaltyDiscount > 0 && (
                            <div className="flex justify-between text-purple-600">
                                <span>Loyalty Discount</span>
                                <span>-{formatCurrency(loyaltyDiscount)}</span>
                            </div>
                        )}
                        <div className="flex justify-between text-gray-900 font-bold text-base pt-1.5 border-t border-gray-200">
                            <span>Total</span>
                            <span className="text-violet-700">{formatCurrency(total)}</span>
                        </div>
                    </div>
                )}

                {/* Checkout button */}
                <div className="p-3 border-t border-gray-200">
                    <Button
                        className="w-full"
                        size="lg"
                        disabled={cartItems.length === 0}
                        onClick={() => {
                            setPayments([{ method: 'cash', amount: total.toFixed(2) }]);
                            setPayModal(true);
                        }}
                    >
                        <CreditCard className="w-5 h-5" />
                        Charge {cartItems.length > 0 ? formatCurrency(total) : ''}
                    </Button>
                </div>
            </div>

            {/* Payment Modal */}
            <Modal open={payModal} onClose={() => setPayModal(false)} title="Process Payment" size="md">
                <div className="space-y-5">
                    {/* Order summary */}
                    <div className="bg-gray-50 rounded-xl p-4 space-y-1 text-sm">
                        <div className="flex justify-between text-gray-600"><span>Subtotal</span><span>{formatCurrency(subtotal)}</span></div>
                        {discountAmt > 0 && <div className="flex justify-between text-green-600"><span>Discount</span><span>-{formatCurrency(discountAmt)}</span></div>}
                        <div className="flex justify-between text-gray-600"><span>VAT 5%</span><span>{formatCurrency(vatAmount)}</span></div>
                        <div className="flex justify-between text-gray-600"><span>Service 10%</span><span>{formatCurrency(scAmount)}</span></div>
                        <div className="flex justify-between font-bold text-base pt-2 border-t border-gray-200">
                            <span>Total Due</span>
                            <span className="text-violet-700">{formatCurrency(total)}</span>
                        </div>
                    </div>

                    {/* Payment method */}
                    <div>
                        <p className="text-sm font-medium text-gray-700 mb-2">Payment Method</p>
                        <div className="grid grid-cols-5 gap-2">
                            {PAYMENT_METHODS.map(pm => (
                                <button
                                    key={pm.value}
                                    onClick={() => setPayments([{ method: pm.value, amount: payments[0]?.amount || '' }])}
                                    className={cn(
                                        'flex flex-col items-center py-3 px-2 rounded-xl border-2 text-xs font-medium transition-all',
                                        payments[0]?.method === pm.value
                                            ? 'border-violet-600 bg-violet-50 text-violet-700'
                                            : 'border-gray-200 text-gray-600 hover:border-gray-300',
                                    )}
                                >
                                    <pm.icon className="w-5 h-5 mb-1" />
                                    {pm.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Amount */}
                    <div>
                        <div className="flex items-center justify-between mb-2">
                            <p className="text-sm font-medium text-gray-700">Amount Received</p>
                            <button onClick={exactChange} className="text-xs text-violet-600 hover:underline">Exact amount</button>
                        </div>
                        <input
                            type="number"
                            value={payments[0]?.amount || ''}
                            onChange={e => setPaymentAmount(0, e.target.value)}
                            className="w-full text-2xl font-bold text-center border-2 border-gray-300 rounded-xl py-3 focus:outline-none focus:border-violet-500"
                            placeholder="0.00"
                            autoFocus
                        />
                    </div>

                    {/* Change */}
                    {amountPaid > 0 && (
                        <div className={cn(
                            'flex justify-between items-center rounded-xl px-4 py-3 text-lg font-bold',
                            change > 0 ? 'bg-green-50 text-green-700' : amountPaid < total ? 'bg-red-50 text-red-700' : 'bg-gray-50 text-gray-700',
                        )}>
                            <span>{amountPaid < total ? 'Balance Due' : 'Change'}</span>
                            <span>{formatCurrency(amountPaid < total ? total - amountPaid : change)}</span>
                        </div>
                    )}

                    <Button
                        className="w-full"
                        size="lg"
                        onClick={handlePlaceOrder}
                        loading={placingOrder}
                        disabled={amountPaid < total}
                    >
                        <Printer className="w-5 h-5" />
                        Complete & Print Receipt
                    </Button>
                </div>
            </Modal>
        </div>
    );
}
