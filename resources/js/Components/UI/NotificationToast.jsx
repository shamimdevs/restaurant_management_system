import { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { removeNotification } from '@/store/notificationSlice';
import { CheckCircle, XCircle, AlertTriangle, Info, X } from 'lucide-react';
import { cn } from '@/lib/utils';

const ICONS = {
    success: { Icon: CheckCircle, cls: 'text-green-500' },
    error: { Icon: XCircle, cls: 'text-red-500' },
    warning: { Icon: AlertTriangle, cls: 'text-yellow-500' },
    info: { Icon: Info, cls: 'text-blue-500' },
};

function Toast({ notification }) {
    const dispatch = useDispatch();
    const { Icon, cls } = ICONS[notification.type] || ICONS.info;

    useEffect(() => {
        const timer = setTimeout(() => dispatch(removeNotification(notification.id)), notification.duration || 4000);
        return () => clearTimeout(timer);
    }, [notification.id]);

    return (
        <div className={cn(
            'flex items-start gap-3 bg-white shadow-lg rounded-xl p-4 w-80',
            'border border-gray-100 animate-slide-in',
        )}>
            <Icon className={cn('w-5 h-5 mt-0.5 flex-shrink-0', cls)} />
            <p className="flex-1 text-sm text-gray-800">{notification.message}</p>
            <button
                onClick={() => dispatch(removeNotification(notification.id))}
                className="text-gray-400 hover:text-gray-600 transition-colors"
            >
                <X className="w-4 h-4" />
            </button>
        </div>
    );
}

export default function NotificationToast() {
    const notifications = useSelector(s => s.notifications.items);

    return (
        <div className="fixed bottom-4 right-4 z-50 flex flex-col gap-2">
            {notifications.map(n => <Toast key={n.id} notification={n} />)}
        </div>
    );
}
