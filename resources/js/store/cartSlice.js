import { createSlice } from '@reduxjs/toolkit';

const initialState = {
    items: [],
    tableSessionId: null,
    customerId: null,
    orderType: 'dine_in',
    couponCode: null,
    couponDiscount: 0,
    loyaltyPointsToRedeem: 0,
    notes: '',
};

const cartSlice = createSlice({
    name: 'cart',
    initialState,
    reducers: {
        addItem(state, action) {
            const { menuItem, variant, modifiers, quantity = 1, notes = '' } = action.payload;
            const key = `${menuItem.id}-${variant?.id || 'default'}-${JSON.stringify(modifiers?.map(m => m.id).sort() || [])}`;

            const existing = state.items.find(i => i.key === key);
            if (existing) {
                existing.quantity += quantity;
            } else {
                const basePrice = variant ? variant.price : menuItem.price;
                const modifierTotal = (modifiers || []).reduce((s, m) => s + parseFloat(m.price || 0), 0);
                state.items.push({
                    key,
                    menuItemId: menuItem.id,
                    variantId: variant?.id || null,
                    name: menuItem.name,
                    variantName: variant?.name || null,
                    modifiers: modifiers || [],
                    unitPrice: basePrice + modifierTotal,
                    quantity,
                    notes,
                    taxGroupId: menuItem.tax_group_id,
                    recipeId: menuItem.recipe_id,
                });
            }
        },
        removeItem(state, action) {
            state.items = state.items.filter(i => i.key !== action.payload);
        },
        updateQuantity(state, action) {
            const { key, quantity } = action.payload;
            if (quantity <= 0) {
                state.items = state.items.filter(i => i.key !== key);
            } else {
                const item = state.items.find(i => i.key === key);
                if (item) item.quantity = quantity;
            }
        },
        clearCart(state) {
            return { ...initialState };
        },
        setTableSession(state, action) {
            state.tableSessionId = action.payload;
        },
        setCustomer(state, action) {
            state.customerId = action.payload;
        },
        setOrderType(state, action) {
            state.orderType = action.payload;
        },
        applyCoupon(state, action) {
            state.couponCode = action.payload.code;
            state.couponDiscount = action.payload.discount;
        },
        removeCoupon(state) {
            state.couponCode = null;
            state.couponDiscount = 0;
        },
        setLoyaltyPoints(state, action) {
            state.loyaltyPointsToRedeem = action.payload;
        },
        setNotes(state, action) {
            state.notes = action.payload;
        },
    },
});

export const {
    addItem, removeItem, updateQuantity, clearCart,
    setTableSession, setCustomer, setOrderType,
    applyCoupon, removeCoupon, setLoyaltyPoints, setNotes,
} = cartSlice.actions;

// Selectors
export const selectCartItems = state => state.cart.items;
export const selectCartSubtotal = state =>
    state.cart.items.reduce((s, i) => s + i.unitPrice * i.quantity, 0);
export const selectCartCount = state =>
    state.cart.items.reduce((s, i) => s + i.quantity, 0);

export default cartSlice.reducer;
