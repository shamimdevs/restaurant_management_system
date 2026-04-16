import { configureStore } from '@reduxjs/toolkit';
import cartReducer from './cartSlice';
import notificationReducer from './notificationSlice';
import kitchenReducer from './kitchenSlice';

export const store = configureStore({
    reducer: {
        cart: cartReducer,
        notifications: notificationReducer,
        kitchen: kitchenReducer,
    },
});
