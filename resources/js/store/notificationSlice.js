import { createSlice } from '@reduxjs/toolkit';

let nextId = 1;

const notificationSlice = createSlice({
    name: 'notifications',
    initialState: { items: [] },
    reducers: {
        addNotification(state, action) {
            state.items.push({ id: nextId++, ...action.payload });
        },
        removeNotification(state, action) {
            state.items = state.items.filter(n => n.id !== action.payload);
        },
    },
});

export const { addNotification, removeNotification } = notificationSlice.actions;

export const notify = (message, type = 'success', duration = 4000) =>
    addNotification({ message, type, duration });

export default notificationSlice.reducer;
