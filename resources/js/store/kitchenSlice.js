import { createSlice } from '@reduxjs/toolkit';

const kitchenSlice = createSlice({
    name: 'kitchen',
    initialState: {
        tickets: [],
        lastPoll: null,
        soundEnabled: true,
    },
    reducers: {
        setTickets(state, action) {
            state.tickets = action.payload;
            state.lastPoll = new Date().toISOString();
        },
        updateTicket(state, action) {
            const idx = state.tickets.findIndex(t => t.id === action.payload.id);
            if (idx !== -1) state.tickets[idx] = action.payload;
        },
        toggleSound(state) {
            state.soundEnabled = !state.soundEnabled;
        },
    },
});

export const { setTickets, updateTicket, toggleSound } = kitchenSlice.actions;
export default kitchenSlice.reducer;
