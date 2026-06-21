import { useEffect, useState } from 'react';
import { Plus, Search } from 'lucide-react';
import { api } from './api';
import { Button } from '../components/Button';
import { Card } from '../components/Card';
import { MetricCard } from '../components/MetricCard';
import { Sidebar } from '../components/Sidebar';

export function App({ initialView = 'dashboard' }) {
  const [view, setView] = useState(initialView);
  const [courses, setCourses] = useState([]);
  const [certificates, setCertificates] = useState([]);
  const [companies, setCompanies] = useState([]);
  const [notifications, setNotifications] = useState([]);
  const [overview, setOverview] = useState(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    let active = true;
    setLoading(true);

    Promise.all([
      api('/courses').catch(() => []),
      api('/certificates').catch(() => []),
      api('/companies').catch(() => []),
      api('/notifications').catch(() => []),
      api('/reports/overview').catch(() => null)
    ]).then(([courseData, certificateData, companyData, notificationData, overviewData]) => {
      if (!active) {
        return;
      }

      setCourses(courseData);
      setCertificates(certificateData);
      setCompanies(companyData);
      setNotifications(notificationData);
      setOverview(overviewData);
      setLoading(false);
    });

    return () => {
      active = false;
    };
  }, []);

  return (
    <div className="min-h-screen bg-canvas text-ink">
      <div className="flex min-h-screen">
        <Sidebar activeView={view} onNavigate={setView} />
        <main className="min-w-0 flex-1">
          <header className="sticky top-0 z-10 border-b border-line bg-panel/95 px-5 py-4 backdrop-blur">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h1 className="text-xl font-semibold capitalize text-ink">{view.replace('-', ' ')}</h1>
                <p className="text-sm text-muted">Clear learning operations for every team.</p>
              </div>
              <div className="flex items-center gap-2">
                <div className="hidden min-h-10 items-center gap-2 rounded-control border border-line bg-white px-3 text-muted md:flex">
                  <Search size={16} />
                  <span className="text-sm">Search</span>
                </div>
                <Button>
                  <Plus size={16} />
                  New
                </Button>
              </div>
            </div>
          </header>

          <div className="p-5">
            {view === 'dashboard' && (
              <Dashboard
                certificates={certificates}
                courses={courses}
                loading={loading}
                notifications={notifications}
                overview={overview}
              />
            )}
            {view === 'courses' && <Courses courses={courses} loading={loading} />}
            {view === 'companies' && <Companies companies={companies} loading={loading} />}
            {view === 'certificates' && <Certificates certificates={certificates} loading={loading} />}
            {view === 'notifications' && <Notifications notifications={notifications} loading={loading} />}
            {!['dashboard', 'courses', 'companies', 'certificates', 'notifications'].includes(view) && (
              <ComingSoon title={view.replace('-', ' ')} />
            )}
          </div>
        </main>
      </div>
    </div>
  );
}

function Dashboard({ courses, certificates, notifications, overview, loading }) {
  return (
    <div className="space-y-5">
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <MetricCard label="Courses" value={loading ? '...' : overview?.courses ?? courses.length} detail="Active learning catalog" />
        <MetricCard label="Certificates" value={loading ? '...' : overview?.certificates ?? certificates.length} detail="Issued credentials" />
        <MetricCard label="Completion" value={`${overview?.completed_enrollments ?? 0}`} detail="Completed enrollments" />
        <MetricCard label="Revenue" value={overview?.revenue ?? 0} detail="Paid order value" />
      </div>
      <Card title="Recent activity">
        {notifications.length > 0 ? (
          <div className="divide-y divide-line">
            {notifications.slice(0, 5).map((notification) => (
              <div className="py-3" key={notification.id}>
                <div className="text-sm font-medium text-ink">{notification.title}</div>
                <div className="text-sm text-muted">{notification.body}</div>
              </div>
            ))}
          </div>
        ) : (
          <div className="rounded-control border border-dashed border-line p-6 text-sm text-muted">
            Learning, payment, and certificate events will appear here as modules emit activity records.
          </div>
        )}
      </Card>
    </div>
  );
}

function Courses({ courses, loading }) {
  if (loading) {
    return <SkeletonRows />;
  }

  return (
    <Card title="Course catalog">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[720px] text-left text-sm">
          <thead className="border-b border-line text-muted">
            <tr>
              <th className="py-3 pr-4 font-medium">Course</th>
              <th className="py-3 pr-4 font-medium">Status</th>
              <th className="py-3 pr-4 font-medium">Visibility</th>
              <th className="py-3 pr-4 font-medium">Price</th>
            </tr>
          </thead>
          <tbody>
            {courses.map((course) => (
              <tr className="border-b border-line last:border-0" key={course.id}>
                <td className="py-3 pr-4 font-medium text-ink">{course.title}</td>
                <td className="py-3 pr-4 capitalize text-muted">{course.status.replace('_', ' ')}</td>
                <td className="py-3 pr-4 capitalize text-muted">{course.visibility}</td>
                <td className="py-3 pr-4 text-muted">{course.price_amount ? `${course.currency} ${course.price_amount}` : 'Free'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </Card>
  );
}

function Certificates({ certificates, loading }) {
  if (loading) {
    return <SkeletonRows />;
  }

  return (
    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      {certificates.map((certificate) => (
        <Card key={certificate.id} title={certificate.display_name}>
          <div className="space-y-2 text-sm text-muted">
            <div>{certificate.certificate_number}</div>
            <div>Issued {certificate.issued_at}</div>
            <Button variant="secondary">Verify</Button>
          </div>
        </Card>
      ))}
      {certificates.length === 0 && (
        <Card title="Certificate wallet">
          <p className="text-sm text-muted">Issued certificates will be available here for view, download, share, and verification.</p>
        </Card>
      )}
    </div>
  );
}

function Companies({ companies, loading }) {
  if (loading) {
    return <SkeletonRows />;
  }

  return (
    <div className="grid gap-4 lg:grid-cols-2">
      {companies.map((company) => (
        <Card key={company.id} title={company.name}>
          <div className="grid gap-3 text-sm text-muted sm:grid-cols-3">
            <div>
              <div className="font-medium text-ink">{company.active_users}</div>
              <div>Employees</div>
            </div>
            <div>
              <div className="font-medium text-ink">{company.departments}</div>
              <div>Departments</div>
            </div>
            <div>
              <div className="font-medium text-ink">{company.seat_limit}</div>
              <div>Seats</div>
            </div>
          </div>
        </Card>
      ))}
      {companies.length === 0 && (
        <Card title="Corporate training">
          <p className="text-sm text-muted">Create companies, departments, and employee assignments from the corporate API.</p>
        </Card>
      )}
    </div>
  );
}

function Notifications({ notifications, loading }) {
  if (loading) {
    return <SkeletonRows />;
  }

  return (
    <Card title="Notification center">
      <div className="divide-y divide-line">
        {notifications.map((notification) => (
          <div className="py-3" key={notification.id}>
            <div className="text-sm font-medium text-ink">{notification.title}</div>
            <div className="text-sm text-muted">{notification.body}</div>
          </div>
        ))}
      </div>
      {notifications.length === 0 && <p className="text-sm text-muted">No notifications yet.</p>}
    </Card>
  );
}

function ComingSoon({ title }) {
  return (
    <Card title={title}>
      <p className="text-sm text-muted">This module is part of the staged roadmap and will connect to the same service and design system.</p>
    </Card>
  );
}

function SkeletonRows() {
  return (
    <div className="space-y-3">
      {[0, 1, 2].map((item) => (
        <div className="h-16 animate-pulse rounded-control bg-slate-200" key={item} />
      ))}
    </div>
  );
}
