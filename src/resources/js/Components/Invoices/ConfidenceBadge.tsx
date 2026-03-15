interface Props {
    confidence: number | null;
}

export default function ConfidenceBadge({ confidence }: Props) {
    if (confidence === null) return null;

    const pct = Math.round(confidence * 100);

    const color =
        pct >= 90
            ? 'bg-green-100 text-green-800'
            : pct >= 70
              ? 'bg-yellow-100 text-yellow-800'
              : 'bg-red-100 text-red-800';

    const label = pct >= 90 ? 'Alta' : pct >= 70 ? 'Media' : 'Baja';

    return (
        <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold ${color}`}>
            {label} ({pct}%)
        </span>
    );
}
