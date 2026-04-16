import { useState, useEffect, useCallback } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { setTickets } from '@/store/kitchenSlice';
import { notify } from '@/store/notificationSlice';
import api from '@/lib/api';
import { minutesDiff, formatTime, cn } from '@/lib/utils';
import { ChefHat, Clock, Check, Bell, BellOff, RefreshCw, Flame } from 'lucide-react';
import Button from '@/Components/UI/Button';
import NotificationToast from '@/Components/UI/NotificationToast';

const TICKET_COLORS = {
    pending: 'border-yellow-400 bg-yellow-50',
    cooking: 'border-orange-400 bg-orange-50',
    ready: 'border-green-400 bg-green-50',
};

const WAIT_COLORS = (mins) => {
    if (mins <= 5) return 'text-green-600 bg-green-100';
    if (mins <= 10) return 'text-yellow-600 bg-yellow-100';
    if (mins <= 15) return 'text-orange-600 bg-orange-100';
    return 'text-red-600 bg-red-100 animate-pulse';
};

function TicketCard({ ticket, onAction }) {
    const [loading, setLoading] = useState(false);
    const waitMins = minutesDiff(ticket.created_at);

    const handleAction = async (action) => {
        setLoading(true);
        try {
            await onAction(ticket.id, action);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className={cn(
            'rounded-2xl border-2 flex flex-col overflow-hidden shadow-sm transition-all',
            TICKET_COLORS[ticket.status] || 'border-gray-300 bg-white',
        )}>
            {/* Header */}
            <div className="px-4 py-3 flex items-center justify-between border-b border-current/10">
                <div>
                    <p className="font-bold text-gray-900 text-lg leading-tight">
                        {ticket.order?.table?.table_number
                            ? `Table ${ticket.order.table.table_number}`
                            : ticket.order?.order_type === 'takeaway' ? 'Takeaway' : 'Delivery'}
                    </p>
                    <p className="text-sm text-gray-600">{ticket.ticket_number}</p>
                </div>
                <div className={cn('px-3 py-1.5 rounded-xl text-sm font-bold flex items-center gap-1.5', WAIT_COLORS(waitMins))}>
                    <Clock className="w-4 h-4" />
                    {waitMins}m
                </div>
            </div>

            {/* Items */}
            <div className="flex-1 px-4 py-3 space-y-2 min-h-[120px]">
                {ticket.items?.map(item => (
                    <div key={item.id} className="flex items-start gap-2">
                        <span className={cn(
                            'text-xs font-bold px-2 py-0.5 rounded-lg flex-shrink-0 mt-0.5',
                            item.status === 'done'
                                ? 'bg-green-200 text-green-800 line-through'
                                : 'bg-gray-200 text-gray-800',
                        )}>
                            ×{item.quantity}
                        </span>
                        <div className="flex-1 min-w-0">
                            <p className={cn(
                                'text-sm font-semibold',
                                item.status === 'done' ? 'text-gray-400 line-through' : 'text-gray-900',
                            )}>
                                {item.name}
                            </p>
                            {item.variant_name && <p className="text-xs text-gray-500">{item.variant_name}</p>}
                            {item.notes && <p className="text-xs text-amber-600 italic">Note: {item.notes}</p>}
                            {item.modifiers?.map(m => (
                                <p key={m.id} className="text-xs text-gray-500">+ {m.name}</p>
                            ))}
                        </div>
                    </div>
                ))}
            </div>

            {/* Actions */}
            <div className="px-4 py-3 border-t border-current/10">
                {ticket.status === 'pending' && (
                    <Button
                        className="w-full"
                        variant="warning"
                        onClick={() => handleAction('start')}
                        loading={loading}
                        icon={Flame}
                    >
                        Start Cooking
                    </Button>
                )}
                {ticket.status === 'cooking' && (
                    <Button
                        className="w-full"
                        variant="success"
                        onClick={() => handleAction('ready')}
                        loading={loading}
                        icon={Check}
                    >
                        Mark Ready
                    </Button>
                )}
                {ticket.status === 'ready' && (
                    <Button
                        className="w-full"
                        variant="primary"
                        onClick={() => handleAction('served')}
                        loading={loading}
                        icon={Check}
                    >
                        Mark Served
                    </Button>
                )}
                <p className="text-xs text-center text-gray-400 mt-1">{formatTime(ticket.created_at)}</p>
            </div>
        </div>
    );
}

export default function KitchenIndex() {
    const dispatch = useDispatch();
    const tickets = useSelector(s => s.kitchen.tickets);
    const soundEnabled = useSelector(s => s.kitchen.soundEnabled);
    const [loading, setLoading] = useState(false);
    const [lastCount, setLastCount] = useState(0);

    const fetchTickets = useCallback(async () => {
        try {
            const { data } = await api.get('/kitchen/tickets');
            const newTickets = data;

            // Play sound on new ticket
            const newCount = newTickets.filter(t => t.status === 'pending').length;
            if (newCount > lastCount && soundEnabled) {
                // Simple browser beep via oscillator
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    const osc = ctx.createOscillator();
                    osc.connect(ctx.destination);
                    osc.frequency.setValueAtTime(880, ctx.currentTime);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.3);
                } catch {}
            }
            setLastCount(newCount);
            dispatch(setTickets(newTickets));
        } catch {}
    }, [dispatch, lastCount, soundEnabled]);

    useEffect(() => {
        fetchTickets();
        const interval = setInterval(fetchTickets, 8000); // poll every 8s
        return () => clearInterval(interval);
    }, [fetchTickets]);

    const handleAction = async (ticketId, action) => {
        const actionMap = {
            start: () => api.post(`/kitchen/tickets/${ticketId}/start`),
            ready: () => api.post(`/kitchen/tickets/${ticketId}/ready`),
            served: () => api.post(`/kitchen/tickets/${ticketId}/served`),
        };
        try {
            await actionMap[action]();
            await fetchTickets();
        } catch {
            dispatch(notify('Action failed', 'error'));
        }
    };

    const pending = tickets.filter(t => t.status === 'pending');
    const cooking = tickets.filter(t => t.status === 'cooking');
    const ready = tickets.filter(t => t.status === 'ready');

    return (
        <div className="min-h-screen bg-gray-950 p-4 -m-6">
            {/* KDS Header */}
            <div className="flex items-center justify-between mb-6">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-orange-500 flex items-center justify-center">
                        <ChefHat className="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h1 className="text-white text-xl font-bold">Kitchen Display</h1>
                        <p className="text-gray-400 text-sm">{new Date().toLocaleTimeString('en-BD')}</p>
                    </div>
                </div>
                <div className="flex items-center gap-3">
                    <div className="flex gap-3 text-sm">
                        <span className="flex items-center gap-1.5 bg-yellow-500/20 text-yellow-400 px-3 py-1.5 rounded-lg font-semibold">
                            <div className="w-2 h-2 rounded-full bg-yellow-400 animate-pulse" />
                            {pending.length} Pending
                        </span>
                        <span className="flex items-center gap-1.5 bg-orange-500/20 text-orange-400 px-3 py-1.5 rounded-lg font-semibold">
                            <div className="w-2 h-2 rounded-full bg-orange-400 animate-pulse" />
                            {cooking.length} Cooking
                        </span>
                        <span className="flex items-center gap-1.5 bg-green-500/20 text-green-400 px-3 py-1.5 rounded-lg font-semibold">
                            <div className="w-2 h-2 rounded-full bg-green-400" />
                            {ready.length} Ready
                        </span>
                    </div>
                    <button
                        onClick={fetchTickets}
                        className="p-2 bg-gray-800 text-gray-300 rounded-lg hover:bg-gray-700 transition-colors"
                    >
                        <RefreshCw className="w-4 h-4" />
                    </button>
                </div>
            </div>

            {/* Three-column board */}
            <div className="grid grid-cols-3 gap-4 h-[calc(100vh-8rem)]">
                {/* Pending */}
                <div className="flex flex-col gap-2 overflow-y-auto">
                    <div className="bg-yellow-500/20 rounded-xl px-4 py-2 flex items-center gap-2 sticky top-0">
                        <div className="w-3 h-3 rounded-full bg-yellow-400 animate-pulse" />
                        <span className="text-yellow-300 font-bold text-sm">PENDING ({pending.length})</span>
                    </div>
                    {pending.map(t => (
                        <TicketCard key={t.id} ticket={t} onAction={handleAction} />
                    ))}
                    {pending.length === 0 && (
                        <div className="flex items-center justify-center h-40 text-gray-600 text-sm">No pending orders</div>
                    )}
                </div>

                {/* Cooking */}
                <div className="flex flex-col gap-2 overflow-y-auto">
                    <div className="bg-orange-500/20 rounded-xl px-4 py-2 flex items-center gap-2 sticky top-0">
                        <div className="w-3 h-3 rounded-full bg-orange-400 animate-pulse" />
                        <span className="text-orange-300 font-bold text-sm">COOKING ({cooking.length})</span>
                    </div>
                    {cooking.map(t => (
                        <TicketCard key={t.id} ticket={t} onAction={handleAction} />
                    ))}
                    {cooking.length === 0 && (
                        <div className="flex items-center justify-center h-40 text-gray-600 text-sm">Nothing cooking</div>
                    )}
                </div>

                {/* Ready */}
                <div className="flex flex-col gap-2 overflow-y-auto">
                    <div className="bg-green-500/20 rounded-xl px-4 py-2 flex items-center gap-2 sticky top-0">
                        <div className="w-3 h-3 rounded-full bg-green-400" />
                        <span className="text-green-300 font-bold text-sm">READY ({ready.length})</span>
                    </div>
                    {ready.map(t => (
                        <TicketCard key={t.id} ticket={t} onAction={handleAction} />
                    ))}
                    {ready.length === 0 && (
                        <div className="flex items-center justify-center h-40 text-gray-600 text-sm">None ready yet</div>
                    )}
                </div>
            </div>

            <NotificationToast />
        </div>
    );
}
