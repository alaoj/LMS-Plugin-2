import { Award, Bell, BookOpen, Building2, CreditCard, LayoutDashboard, Settings, Users } from 'lucide-react';

const items = [
  { label: 'Dashboard', icon: LayoutDashboard, view: 'dashboard' },
  { label: 'Courses', icon: BookOpen, view: 'courses' },
  { label: 'Learners', icon: Users, view: 'learners' },
  { label: 'Companies', icon: Building2, view: 'companies' },
  { label: 'Certificates', icon: Award, view: 'certificates' },
  { label: 'Payments', icon: CreditCard, view: 'payments' },
  { label: 'Notifications', icon: Bell, view: 'notifications' },
  { label: 'Settings', icon: Settings, view: 'settings' }
];

export function Sidebar({ activeView, onNavigate }) {
  return (
    <aside className="hidden w-64 shrink-0 border-r border-line bg-panel px-4 py-5 lg:block">
      <div className="mb-8 px-2">
        <div className="text-lg font-semibold text-ink">Zadora LMS</div>
        <div className="text-sm text-muted">Workforce learning</div>
      </div>
      <nav className="space-y-1">
        {items.map((item) => {
          const Icon = item.icon;
          const active = activeView === item.view;

          return (
            <button
              key={item.view}
              className={`flex w-full items-center gap-3 rounded-control px-3 py-2 text-left text-sm transition ${
                active ? 'bg-blue-50 text-brand' : 'text-muted hover:bg-slate-50 hover:text-ink'
              }`}
              onClick={() => onNavigate(item.view)}
              type="button"
            >
              <Icon size={18} />
              <span>{item.label}</span>
            </button>
          );
        })}
      </nav>
    </aside>
  );
}
