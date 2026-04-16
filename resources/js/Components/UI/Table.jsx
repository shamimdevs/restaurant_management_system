import { cn } from '@/lib/utils';

export function DataTable({ columns, data, loading, emptyMessage = 'No records found', className }) {
    return (
        <div className={cn('overflow-x-auto rounded-xl border border-gray-200', className)}>
            <table className="w-full text-sm">
                <thead>
                    <tr className="bg-gray-50 border-b border-gray-200">
                        {columns.map((col, i) => (
                            <th
                                key={i}
                                className={cn(
                                    'px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide',
                                    col.className,
                                )}
                            >
                                {col.header}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-100 bg-white">
                    {loading ? (
                        Array.from({ length: 5 }).map((_, i) => (
                            <tr key={i}>
                                {columns.map((_, j) => (
                                    <td key={j} className="px-4 py-3">
                                        <div className="h-4 bg-gray-100 rounded animate-pulse" />
                                    </td>
                                ))}
                            </tr>
                        ))
                    ) : data.length === 0 ? (
                        <tr>
                            <td colSpan={columns.length} className="px-4 py-12 text-center text-gray-400">
                                {emptyMessage}
                            </td>
                        </tr>
                    ) : (
                        data.map((row, i) => (
                            <tr key={row.id || i} className="hover:bg-gray-50 transition-colors">
                                {columns.map((col, j) => (
                                    <td key={j} className={cn('px-4 py-3 text-gray-800', col.cellClass)}>
                                        {col.render ? col.render(row) : row[col.key]}
                                    </td>
                                ))}
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}
