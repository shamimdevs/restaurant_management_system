import { useEffect } from 'react';
import { X } from 'lucide-react';
import { cn } from '@/lib/utils';

const SIZES = {
    sm: 'max-w-md',
    md: 'max-w-xl',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
    full: 'max-w-7xl',
};

export default function Modal({ open, onClose, title, children, size = 'md', className }) {
    useEffect(() => {
        const handleKey = (e) => { if (e.key === 'Escape') onClose?.(); };
        if (open) document.addEventListener('keydown', handleKey);
        return () => document.removeEventListener('keydown', handleKey);
    }, [open, onClose]);

    if (!open) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-black/50 backdrop-blur-sm"
                onClick={onClose}
            />

            {/* Panel */}
            <div className={cn(
                'relative w-full bg-white rounded-2xl shadow-2xl flex flex-col max-h-[90vh]',
                SIZES[size],
                className,
            )}>
                {/* Header */}
                {title && (
                    <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                        <h3 className="text-lg font-semibold text-gray-900">{title}</h3>
                        <button
                            onClick={onClose}
                            className="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                        >
                            <X className="w-5 h-5" />
                        </button>
                    </div>
                )}

                {/* Body */}
                <div className="flex-1 overflow-y-auto p-6">
                    {children}
                </div>
            </div>
        </div>
    );
}
