import { Card } from './Card';

export function MetricCard({ label, value, detail }) {
  return (
    <Card>
      <div className="text-sm text-muted">{label}</div>
      <div className="mt-2 text-3xl font-semibold text-ink">{value}</div>
      {detail && <div className="mt-2 text-sm text-muted">{detail}</div>}
    </Card>
  );
}
