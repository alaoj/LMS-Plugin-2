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
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    let active = true;
    setLoading(true);

    Promise.all([
      api('/courses').catch(() => []),
      api('/certificates').catch(() => [])
    ]).then(([courseData, certificateData]) => {
      if (!active) {
        return;
      }

      setCourses(courseData);
      setCertificates(certificateData);
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
            {view === 'dashboard' && <Dashboard courses={courses} certificates={certificates} loading={loading} />}
            {view === 'courses' && <Courses courses={courses} loading={loading} />}
            {view === 'certificates' && <Certificates certificates={certificates} loading={loading} />}
            {!['dashboard', 'courses', 'certificates'].includes(view) && <ComingSoon title={view.replace('-', ' ')} />}
          </div>
        </main>
      </div>
    </div>
  );
}

function Dashboard({ courses, certificates, loading }) {
  return (
    <div className="space-y-5">
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <MetricCard label="Courses" value={loading ? '...' : courses.length} detail="Active learning catalog" />
        <MetricCard label="Certificates" value={loading ? '...' : certificates.length} detail="Issued credentials" />
        <MetricCard label="Completion" value="0%" detail="Reporting baseline ready" />
        <MetricCard label="Revenue" value="0" detail="Payments foundation ready" />
      </div>
      <Card title="Recent activity">
        <div className="rounded-control border border-dashed border-line p-6 text-sm text-muted">
          Learning, payment, and certificate events will appear here as modules emit activity records.
        </div>
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
