import { createSlice } from '@reduxjs/toolkit';

const stored = localStorage.getItem('selectedBranch');

const branchSlice = createSlice({
    name: 'branch',
    initialState: {
        selected: stored ? JSON.parse(stored) : null, // { id, name, code }
        list: [],
    },
    reducers: {
        setSelectedBranch(state, action) {
            state.selected = action.payload;
            if (action.payload) {
                localStorage.setItem('selectedBranch', JSON.stringify(action.payload));
            } else {
                localStorage.removeItem('selectedBranch');
            }
        },
        setBranchList(state, action) {
            state.list = action.payload;
        },
    },
});

export const { setSelectedBranch, setBranchList } = branchSlice.actions;
export default branchSlice.reducer;
