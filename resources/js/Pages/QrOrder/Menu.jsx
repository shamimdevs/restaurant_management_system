import { useState, useMemo } from 'react';
import { UtensilsCrossed, ShoppingCart, Plus, Minus, Trash2, ChevronDown, ChevronUp, CheckCircle } from 'lucide-react';
import { cn } from '@/lib/utils';
import axios from 'axios';

export default function QrOrderMenu({ table, branch, categories }) {
    const [cart, setCart] = useState([]);
    const [activeCategory, setActiveCategory] = useState(categories?.[0]?.id ?? null);
    const [cartOpen, setCartOpen] = useState(false);
    const [notes, setNotes] = useState('');
    const [placing, setPlacing] = useState(false);
    const [placed, setPlaced] = useState(false);

    const cartTotal = useMemo(() => cart.reduce((s, i) => s + i.price * i.qty, 0), [cart]);
    const cartCount = useMemo(() => cart.reduce((s, i) => s + i.qty, 0), [cart]);
    const vat = Math.round(cartTotal * 0.05 * 100) / 100;

    const addItem = (item) => {
        setCart(prev => {
            const existing = prev.find(c => c.id === item.id);
            if (existing) return prev.map(c => c.id === item.id ? { ...c, qty: c.qty + 1 } : c);
            return [...prev, { id: item.id, name: item.name, price: item.base_price, qty: 1 }];
        });
    };

    const removeItem = (id) => {
        setCart(prev => {
            const existing = prev.find(c => c.id === id);
            if (!existing) return prev;
            if (existing.qty <= 1) return prev.filter(c => c.id !== id);
            return prev.map(c => c.id === id ? { ...c, qty: c.qty - 1 } : c);
        });
    };

    const deleteItem = (id) => setCart(prev => prev.filter(c => c.id !== id));

    const placeOrder = async () => {
        if (!cart.length || placing) return;
        setPlacing(true);
        try {
            await axios.post(`/api/qr/${table.qr_code}/order`, {
                items: cart.map(c => ({ menu_item_id: c.id, quantity: c.qty })),
                notes,
            });
            setPlaced(true);
        } catch (e) {
            alert('Failed to place order. Please try again.');
        } finally {
            setPlacing(false);
        }
    };

    const activeItems = useMemo(() => {
        const cat = categories?.find(c => c.id === activeCategory);
        return cat?.menu_items ?? [];
    }, [activeCategory, categories]);

    const cartQty = (id) => cart.find(c => c.id === id)?.qty ?? 0;

    if (placed) {
        return (
            <div className="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-violet-50 to-indigo-50 p-6 text-center">
                <div className="bg-white rounded-3xl shadow-xl p-10 max-w-sm w-full">
                    <CheckCircle className="w-20 h-20 text-green-500 mx-auto mb-4" />
                    <h2 className="text-2xl font-bold text-gray-900 mb-2">Order Placed!</h2>
                    <p className="text-gray-500 mb-1">Your order has been sent to the kitchen.</p>
                    <p className="text-sm text-gray-400 mb-6">Table: <strong>{table.name}</strong></p>
                    <p className="text-xs text-gray-400">A staff member will assist you shortly.</p>
                    <button
                        onClick={() => { setCart([]); setNotes(''); setPlaced(false); }}
                        className="mt-6 w-full py-3 rounded-xl bg-violet-600 text-white font-semibold text-sm hover:bg-violet-700 transition-colors"
                    >
                        Order More
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50 pb-32">
            {/* Header */}
            <div className="bg-gradient-to-r from-violet-700 to-indigo-700 text-white px-4 py-5 sticky top-0 z-20 shadow-lg">
                <div className="flex items-center gap-3 max-w-lg mx-auto">
                    <div className="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                        <UtensilsCrossed className="w-5 h-5" />
                    </div>
                    <div>
                        <p className="font-bold text-sm leading-tight">{branch?.company?.name || 'Restaurant'}</p>
                        <p className="text-white/70 text-xs">{table.name} · Scan & Order</p>
                    </div>
                </div>
            </div>

            {/* Category Tabs */}
            <div className="bg-white border-b border-gray-200 sticky top-[72px] z-10 overflow-x-auto">
                <div className="flex gap-1 px-4 py-2 max-w-lg mx-auto">
                    {categories?.map(cat => (
                        <button
                            key={cat.id}
                            onClick={() => setActiveCategory(cat.id)}
                            className={cn(
                                'flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold transition-all',
                                activeCategory === cat.id
                                    ? 'bg-violet-600 text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            )}
                        >
                            {cat.name}
                        </button>
                    ))}
                </div>
            </div>

            {/* Menu Items */}
            <div className="max-w-lg mx-auto px-4 py-4 space-y-3">
                {activeItems.length === 0 && (
                    <p className="text-center text-gray-400 text-sm py-12">No items available in this category.</p>
                )}
                {activeItems.map(item => {
                    const qty = cartQty(item.id);
                    return (
                        <div key={item.id} className="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
                            <div className="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 text-2xl bg-gray-50">
                                {item.type === 'beverage' ? '🥤' : '🍽️'}
                            </div>
                            <div className="flex-1 min-w-0">
                                <p className="text-sm font-semibold text-gray-900 truncate">{item.name}</p>
                                {item.preparation_time && (
                                    <p className="text-xs text-gray-400">~{item.preparation_time} min</p>
                                )}
                                <p className="text-sm font-bold text-violet-700 mt-0.5">৳{item.base_price}</p>
                            </div>
                            {qty === 0 ? (
                                <button
                                    onClick={() => addItem(item)}
                                    className="w-9 h-9 rounded-full bg-violet-600 text-white flex items-center justify-center shadow-sm hover:bg-violet-700 transition-colors flex-shrink-0"
                                >
                                    <Plus className="w-4 h-4" />
                                </button>
                            ) : (
                                <div className="flex items-center gap-2 flex-shrink-0">
                                    <button onClick={() => removeItem(item.id)} className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors">
                                        <Minus className="w-3.5 h-3.5 text-gray-700" />
                                    </button>
                                    <span className="w-5 text-center text-sm font-bold text-gray-900">{qty}</span>
                                    <button onClick={() => addItem(item)} className="w-8 h-8 rounded-full bg-violet-600 text-white flex items-center justify-center hover:bg-violet-700 transition-colors">
                                        <Plus className="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>

            {/* Cart Bottom Sheet */}
            {cartCount > 0 && (
                <div className="fixed bottom-0 left-0 right-0 z-30 max-w-lg mx-auto">
                    {/* Cart Summary toggle */}
                    <button
                        onClick={() => setCartOpen(!cartOpen)}
                        className="w-full bg-violet-700 text-white px-5 py-4 flex items-center justify-between shadow-xl"
                    >
                        <div className="flex items-center gap-3">
                            <span className="bg-white text-violet-700 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold">
                                {cartCount}
                            </span>
                            <span className="font-semibold text-sm">View Order</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="font-bold">৳{cartTotal.toLocaleString()}</span>
                            {cartOpen ? <ChevronDown className="w-4 h-4" /> : <ChevronUp className="w-4 h-4" />}
                        </div>
                    </button>

                    {/* Expanded cart */}
                    {cartOpen && (
                        <div className="bg-white border-t border-gray-200 px-4 pt-4 pb-6 shadow-2xl max-h-80 overflow-y-auto">
                            <div className="space-y-3">
                                {cart.map(item => (
                                    <div key={item.id} className="flex items-center gap-3">
                                        <div className="flex items-center gap-1.5">
                                            <button onClick={() => removeItem(item.id)} className="w-7 h-7 rounded-full bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors">
                                                <Minus className="w-3 h-3 text-gray-700" />
                                            </button>
                                            <span className="w-5 text-center text-sm font-bold">{item.qty}</span>
                                            <button onClick={() => addItem(item)} className="w-7 h-7 rounded-full bg-violet-100 flex items-center justify-center hover:bg-violet-200 transition-colors">
                                                <Plus className="w-3 h-3 text-violet-700" />
                                            </button>
                                        </div>
                                        <p className="flex-1 text-sm text-gray-800 truncate">{item.name}</p>
                                        <p className="text-sm font-semibold text-gray-900">৳{(item.price * item.qty).toLocaleString()}</p>
                                        <button onClick={() => deleteItem(item.id)} className="text-red-400 hover:text-red-600 transition-colors">
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    </div>
                                ))}
                            </div>

                            <div className="mt-4 pt-3 border-t border-gray-100 space-y-1 text-sm">
                                <div className="flex justify-between text-gray-600">
                                    <span>Subtotal</span><span>৳{cartTotal.toLocaleString()}</span>
                                </div>
                                <div className="flex justify-between text-gray-600">
                                    <span>VAT (5%)</span><span>৳{vat.toLocaleString()}</span>
                                </div>
                                <div className="flex justify-between font-bold text-gray-900 text-base pt-1">
                                    <span>Total</span><span>৳{(cartTotal + vat).toLocaleString()}</span>
                                </div>
                            </div>

                            <textarea
                                value={notes}
                                onChange={e => setNotes(e.target.value)}
                                placeholder="Any special requests? (optional)"
                                rows={2}
                                className="mt-3 w-full text-sm border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-400 resize-none"
                            />

                            <button
                                onClick={placeOrder}
                                disabled={placing}
                                className="mt-3 w-full py-3.5 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-bold text-sm shadow-lg hover:opacity-90 transition-opacity disabled:opacity-60"
                            >
                                {placing ? 'Placing Order...' : 'Place Order'}
                            </button>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
