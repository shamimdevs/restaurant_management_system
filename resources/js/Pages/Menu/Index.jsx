import { useState, useEffect } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { useDispatch } from 'react-redux';
import { notify } from '@/store/notificationSlice';
import api from '@/lib/api';
import { formatCurrency } from '@/lib/utils';
import {
    Plus, Search, Edit2, Trash2, ToggleLeft, ToggleRight,
    Image, Tag, ChevronRight, Package,
} from 'lucide-react';
import Button from '@/Components/UI/Button';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import Badge from '@/Components/UI/Badge';
import { DataTable } from '@/Components/UI/Table';

export default function MenuIndex({ categories: initialCategories }) {
    const dispatch = useDispatch();
    const [categories, setCategories] = useState(initialCategories || []);
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(false);
    const [activeCategory, setActiveCategory] = useState(null);
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);

    const [itemModal, setItemModal] = useState(false);
    const [editItem, setEditItem] = useState(null);
    const [catModal, setCatModal] = useState(false);
    const [saving, setSaving] = useState(false);

    const [form, setForm] = useState({
        name: '', description: '', price: '', category_id: '',
        sku: '', barcode: '', is_available: true, is_taxable: true,
        prep_time: '', calories: '', sort_order: 0,
    });
    const [catForm, setCatForm] = useState({ name: '', color: '#7c3aed', sort_order: 0 });

    const loadItems = async () => {
        setLoading(true);
        try {
            const { data } = await api.get('/menu/items', {
                params: { category_id: activeCategory, search, page, per_page: 20 },
            });
            setItems(data.data || []);
            setTotalPages(data.last_page || 1);
        } catch {
            dispatch(notify('Failed to load items', 'error'));
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => { loadItems(); }, [activeCategory, search, page]);

    const openCreate = () => {
        setEditItem(null);
        setForm({ name: '', description: '', price: '', category_id: activeCategory || '', sku: '', barcode: '', is_available: true, is_taxable: true, prep_time: '', calories: '', sort_order: 0 });
        setItemModal(true);
    };

    const openEdit = (item) => {
        setEditItem(item);
        setForm({
            name: item.name, description: item.description || '',
            price: item.price, category_id: item.category_id,
            sku: item.sku || '', barcode: item.barcode || '',
            is_available: item.is_available, is_taxable: item.is_taxable,
            prep_time: item.prep_time || '', calories: item.calories || '',
            sort_order: item.sort_order || 0,
        });
        setItemModal(true);
    };

    const saveItem = async () => {
        setSaving(true);
        try {
            if (editItem) {
                await api.put(`/menu/items/${editItem.id}`, form);
                dispatch(notify('Item updated', 'success'));
            } else {
                await api.post('/menu/items', form);
                dispatch(notify('Item created', 'success'));
            }
            setItemModal(false);
            loadItems();
        } catch (err) {
            dispatch(notify(err.response?.data?.message || 'Save failed', 'error'));
        } finally {
            setSaving(false);
        }
    };

    const toggleAvailability = async (item) => {
        try {
            await api.patch(`/menu/items/${item.id}/toggle`);
            setItems(prev => prev.map(i => i.id === item.id ? { ...i, is_available: !i.is_available } : i));
        } catch {
            dispatch(notify('Update failed', 'error'));
        }
    };

    const deleteItem = async (item) => {
        if (!confirm(`Delete "${item.name}"?`)) return;
        try {
            await api.delete(`/menu/items/${item.id}`);
            dispatch(notify('Item deleted', 'success'));
            loadItems();
        } catch {
            dispatch(notify('Delete failed', 'error'));
        }
    };

    const saveCategory = async () => {
        setSaving(true);
        try {
            await api.post('/menu/categories', catForm);
            dispatch(notify('Category created', 'success'));
            setCatModal(false);
            // Reload page to get updated categories
            window.location.reload();
        } catch (err) {
            dispatch(notify(err.response?.data?.message || 'Failed', 'error'));
        } finally {
            setSaving(false);
        }
    };

    const columns = [
        {
            header: 'Item',
            render: (item) => (
                <div className="flex items-center gap-3">
                    {item.image
                        ? <img src={`/storage/${item.image}`} alt={item.name} className="w-9 h-9 rounded-lg object-cover flex-shrink-0" />
                        : <div className="w-9 h-9 rounded-lg bg-violet-100 flex items-center justify-center flex-shrink-0 text-violet-600 font-bold text-sm">{item.name[0]}</div>
                    }
                    <div>
                        <p className="font-medium text-gray-900">{item.name}</p>
                        {item.sku && <p className="text-xs text-gray-500">SKU: {item.sku}</p>}
                    </div>
                </div>
            ),
        },
        { header: 'Category', render: (item) => <span className="text-sm text-gray-600">{item.category?.name}</span> },
        { header: 'Price', render: (item) => <span className="font-semibold text-gray-900">{formatCurrency(item.price)}</span> },
        {
            header: 'Status',
            render: (item) => (
                <button onClick={() => toggleAvailability(item)} className="flex items-center gap-1.5 transition-colors">
                    {item.is_available
                        ? <ToggleRight className="w-5 h-5 text-green-500" />
                        : <ToggleLeft className="w-5 h-5 text-gray-400" />}
                    <span className={`text-xs font-medium ${item.is_available ? 'text-green-600' : 'text-gray-500'}`}>
                        {item.is_available ? 'Available' : 'Sold Out'}
                    </span>
                </button>
            ),
        },
        {
            header: '',
            render: (item) => (
                <div className="flex items-center gap-2">
                    <button onClick={() => openEdit(item)} className="p-1.5 text-gray-400 hover:text-violet-600 hover:bg-violet-50 rounded-lg transition-colors">
                        <Edit2 className="w-4 h-4" />
                    </button>
                    <button onClick={() => deleteItem(item)} className="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                        <Trash2 className="w-4 h-4" />
                    </button>
                </div>
            ),
        },
    ];

    return (
        <AppLayout title="Menu Management">
            <div className="flex gap-6">
                {/* Category sidebar */}
                <div className="w-52 flex-shrink-0">
                    <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm">
                        <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <span className="text-sm font-semibold text-gray-700">Categories</span>
                            <button onClick={() => { setCatForm({ name: '', color: '#7c3aed', sort_order: 0 }); setCatModal(true); }}
                                className="p-1 rounded-md hover:bg-gray-100 text-gray-400 hover:text-violet-600 transition-colors">
                                <Plus className="w-4 h-4" />
                            </button>
                        </div>
                        <div className="divide-y divide-gray-100">
                            <button
                                onClick={() => setActiveCategory(null)}
                                className={`w-full px-4 py-2.5 text-sm text-left transition-colors ${!activeCategory ? 'bg-violet-50 text-violet-700 font-medium' : 'text-gray-600 hover:bg-gray-50'}`}
                            >
                                All Items
                            </button>
                            {categories.map(cat => (
                                <button
                                    key={cat.id}
                                    onClick={() => setActiveCategory(cat.id)}
                                    className={`w-full px-4 py-2.5 text-sm text-left flex items-center justify-between transition-colors ${activeCategory === cat.id ? 'bg-violet-50 text-violet-700 font-medium' : 'text-gray-600 hover:bg-gray-50'}`}
                                >
                                    <div className="flex items-center gap-2">
                                        <div className="w-2 h-2 rounded-full flex-shrink-0" style={{ backgroundColor: cat.color || '#7c3aed' }} />
                                        <span className="truncate">{cat.name}</span>
                                    </div>
                                    <span className="text-xs text-gray-400">{cat.menu_items_count}</span>
                                </button>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Main content */}
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="relative flex-1 max-w-sm">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <input
                                value={search}
                                onChange={e => { setSearch(e.target.value); setPage(1); }}
                                placeholder="Search items..."
                                className="w-full pl-9 pr-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500"
                            />
                        </div>
                        <Button icon={Plus} onClick={openCreate}>Add Item</Button>
                    </div>

                    <DataTable columns={columns} data={items} loading={loading} emptyMessage="No items in this category" />

                    {totalPages > 1 && (
                        <div className="flex justify-center gap-2 mt-4">
                            <Button variant="secondary" size="sm" disabled={page === 1} onClick={() => setPage(p => p - 1)}>Previous</Button>
                            <span className="text-sm text-gray-600 flex items-center px-3">Page {page} of {totalPages}</span>
                            <Button variant="secondary" size="sm" disabled={page === totalPages} onClick={() => setPage(p => p + 1)}>Next</Button>
                        </div>
                    )}
                </div>
            </div>

            {/* Item Modal */}
            <Modal open={itemModal} onClose={() => setItemModal(false)} title={editItem ? 'Edit Menu Item' : 'Add Menu Item'} size="lg">
                <div className="grid grid-cols-2 gap-4">
                    <Input label="Name" value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} required className="col-span-2" containerClass="col-span-2" />
                    <Input label="Price (৳)" type="number" step="0.01" value={form.price} onChange={e => setForm({ ...form, price: e.target.value })} required />
                    <div className="flex flex-col gap-1">
                        <label className="text-sm font-medium text-gray-700">Category <span className="text-red-500">*</span></label>
                        <select value={form.category_id} onChange={e => setForm({ ...form, category_id: e.target.value })}
                            className="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="">Select category</option>
                            {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                        </select>
                    </div>
                    <Input label="SKU" value={form.sku} onChange={e => setForm({ ...form, sku: e.target.value })} />
                    <Input label="Barcode" value={form.barcode} onChange={e => setForm({ ...form, barcode: e.target.value })} />
                    <Input label="Prep Time (mins)" type="number" value={form.prep_time} onChange={e => setForm({ ...form, prep_time: e.target.value })} />
                    <Input label="Calories" type="number" value={form.calories} onChange={e => setForm({ ...form, calories: e.target.value })} />
                    <div className="col-span-2">
                        <label className="text-sm font-medium text-gray-700">Description</label>
                        <textarea value={form.description} onChange={e => setForm({ ...form, description: e.target.value })}
                            rows={3} className="w-full mt-1 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500" />
                    </div>
                    <label className="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" checked={form.is_available} onChange={e => setForm({ ...form, is_available: e.target.checked })} className="rounded text-violet-600" />
                        <span className="text-sm text-gray-700">Available</span>
                    </label>
                    <label className="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" checked={form.is_taxable} onChange={e => setForm({ ...form, is_taxable: e.target.checked })} className="rounded text-violet-600" />
                        <span className="text-sm text-gray-700">Taxable</span>
                    </label>
                </div>
                <div className="flex justify-end gap-3 mt-6">
                    <Button variant="secondary" onClick={() => setItemModal(false)}>Cancel</Button>
                    <Button onClick={saveItem} loading={saving}>{editItem ? 'Update' : 'Create'} Item</Button>
                </div>
            </Modal>

            {/* Category Modal */}
            <Modal open={catModal} onClose={() => setCatModal(false)} title="Add Category" size="sm">
                <div className="space-y-4">
                    <Input label="Name" value={catForm.name} onChange={e => setCatForm({ ...catForm, name: e.target.value })} required />
                    <div>
                        <label className="text-sm font-medium text-gray-700">Color</label>
                        <div className="flex items-center gap-3 mt-1">
                            <input type="color" value={catForm.color} onChange={e => setCatForm({ ...catForm, color: e.target.value })}
                                className="w-10 h-10 rounded-lg cursor-pointer border border-gray-300" />
                            <span className="text-sm text-gray-600">{catForm.color}</span>
                        </div>
                    </div>
                    <Input label="Sort Order" type="number" value={catForm.sort_order} onChange={e => setCatForm({ ...catForm, sort_order: e.target.value })} />
                </div>
                <div className="flex justify-end gap-3 mt-6">
                    <Button variant="secondary" onClick={() => setCatModal(false)}>Cancel</Button>
                    <Button onClick={saveCategory} loading={saving}>Create Category</Button>
                </div>
            </Modal>
        </AppLayout>
    );
}
