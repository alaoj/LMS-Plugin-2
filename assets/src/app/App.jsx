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
  const [certificateTemplates, setCertificateTemplates] = useState([]);
  const [companies, setCompanies] = useState([]);
  const [notifications, setNotifications] = useState([]);
  const [overview, setOverview] = useState(null);
  const [assessmentReport, setAssessmentReport] = useState(null);
  const [submissions, setSubmissions] = useState([]);
  const [learnerReport, setLearnerReport] = useState(null);
  const [selectedCourse, setSelectedCourse] = useState(null);
  const [lessons, setLessons] = useState([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    let active = true;
    setLoading(true);

    Promise.all([
      api('/courses').catch(() => []),
      api('/certificates').catch(() => []),
      api('/certificate-templates').catch(() => []),
      api('/companies').catch(() => []),
      api('/notifications').catch(() => []),
      api('/reports/overview').catch(() => null),
      api('/reports/assessments').catch(() => null),
      api('/assessment-submissions?status=submitted').catch(() => []),
      api('/reports/learner').catch(() => null)
    ]).then(([courseData, certificateData, templateData, companyData, notificationData, overviewData, assessmentData, submissionData, learnerData]) => {
      if (!active) {
        return;
      }

      setCourses(courseData);
      setCertificates(certificateData);
      setCertificateTemplates(templateData);
      setCompanies(companyData);
      setNotifications(notificationData);
      setOverview(overviewData);
      setAssessmentReport(assessmentData);
      setSubmissions(submissionData);
      setLearnerReport(learnerData);
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
                learnerReport={learnerReport}
                loading={loading}
                notifications={notifications}
                overview={overview}
                assessmentReport={assessmentReport}
              />
            )}
            {view === 'courses' && (
              <Courses
                courses={courses}
                lessons={lessons}
                loading={loading}
                onJoinWaitlist={async (course) => {
                  await api(`/courses/${course.id}/waitlist`, { method: 'POST' });
                }}
                onSelectCourse={async (course) => {
                  setSelectedCourse(course);
                  setLessons(await api(`/courses/${course.id}/lessons`).catch(() => []));
                }}
                selectedCourse={selectedCourse}
              />
            )}
            {view === 'assessments' && (
              <Assessments loading={loading} report={assessmentReport} submissions={submissions} />
            )}
            {view === 'companies' && <Companies companies={companies} loading={loading} />}
            {view === 'certificates' && (
              <Certificates certificates={certificates} loading={loading} templates={certificateTemplates} />
            )}
            {view === 'notifications' && <Notifications notifications={notifications} loading={loading} />}
            {!['dashboard', 'courses', 'assessments', 'companies', 'certificates', 'notifications'].includes(view) && (
              <ComingSoon title={view.replace('-', ' ')} />
            )}
          </div>
        </main>
      </div>
    </div>
  );
}

function Dashboard({ assessmentReport, courses, certificates, learnerReport, notifications, overview, loading }) {
  return (
    <div className="space-y-5">
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <MetricCard label="Courses" value={loading ? '...' : overview?.courses ?? courses.length} detail="Active learning catalog" />
        <MetricCard label="Certificates" value={loading ? '...' : overview?.certificates ?? certificates.length} detail="Issued credentials" />
        <MetricCard label="Pending grading" value={assessmentReport?.pending_grading ?? overview?.submissions_pending_grading ?? 0} detail="Instructor review queue" />
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
      <Card title="Continue learning">
        {learnerReport?.courses?.length > 0 ? (
          <div className="divide-y divide-line">
            {learnerReport.courses.slice(0, 4).map((course) => (
              <div className="flex flex-wrap items-center justify-between gap-3 py-3" key={course.enrollment_id}>
                <div>
                  <div className="text-sm font-medium text-ink">{course.course_title}</div>
                  <div className="text-sm text-muted">
                    {course.completed_lessons} of {course.total_lessons} lessons complete
                  </div>
                </div>
                <div className="h-2 w-36 rounded-full bg-slate-100">
                  <div className="h-2 rounded-full bg-brand" style={{ width: `${course.progress_percent}%` }} />
                </div>
              </div>
            ))}
          </div>
        ) : (
          <p className="text-sm text-muted">Assigned courses and lesson progress will appear here.</p>
        )}
      </Card>
    </div>
  );
}

function Assessments({ loading, report, submissions }) {
  if (loading) {
    return <SkeletonRows />;
  }

  return (
    <div className="space-y-5">
      <div className="grid gap-4 md:grid-cols-3">
        <MetricCard label="Assessments" value={report?.total_assessments ?? 0} detail="Configured checks" />
        <MetricCard label="Pending grading" value={report?.pending_grading ?? 0} detail="Needs instructor review" />
        <MetricCard label="Graded" value={report?.graded ?? 0} detail="Completed submissions" />
      </div>
      <Card title="Pending submissions">
        <div className="divide-y divide-line">
          {submissions.map((submission) => (
            <div className="flex flex-wrap items-center justify-between gap-3 py-3" key={submission.id}>
              <div>
                <div className="text-sm font-medium text-ink">Submission #{submission.id}</div>
                <div className="text-sm text-muted">Assessment #{submission.assessment_id} by user #{submission.user_id}</div>
              </div>
              <Button variant="secondary">Grade</Button>
            </div>
          ))}
        </div>
        {submissions.length === 0 && <p className="text-sm text-muted">No submissions are waiting for grading.</p>}
      </Card>
    </div>
  );
}

function Courses({ courses, lessons, loading, onJoinWaitlist, onSelectCourse, selectedCourse }) {
  if (loading) {
    return <SkeletonRows />;
  }

  return (
    <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
      <Card title="Course catalog">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[760px] text-left text-sm">
            <thead className="border-b border-line text-muted">
              <tr>
                <th className="py-3 pr-4 font-medium">Course</th>
                <th className="py-3 pr-4 font-medium">Status</th>
                <th className="py-3 pr-4 font-medium">Visibility</th>
                <th className="py-3 pr-4 font-medium">Price</th>
                <th className="py-3 pr-4 font-medium">Action</th>
              </tr>
            </thead>
            <tbody>
              {courses.map((course) => (
                <tr className="border-b border-line last:border-0" key={course.id}>
                  <td className="py-3 pr-4 font-medium text-ink">{course.title}</td>
                  <td className="py-3 pr-4 capitalize text-muted">{course.status.replace('_', ' ')}</td>
                  <td className="py-3 pr-4 capitalize text-muted">{course.visibility}</td>
                  <td className="py-3 pr-4 text-muted">{course.price_amount ? `${course.currency} ${course.price_amount}` : 'Free'}</td>
                  <td className="py-3 pr-4">
                    {course.status === 'coming_soon' ? (
                      <Button onClick={() => onJoinWaitlist(course)} variant="secondary">Join waitlist</Button>
                    ) : (
                      <Button onClick={() => onSelectCourse(course)} variant="ghost">View lessons</Button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Card>
      <Card title={selectedCourse ? selectedCourse.title : 'Lessons'}>
        {selectedCourse ? (
          <div className="space-y-3">
            {lessons.map((lesson) => (
              <div className="rounded-control border border-line p-3" key={lesson.id}>
                <div className="text-sm font-medium text-ink">{lesson.title}</div>
                <div className="mt-1 text-sm text-muted">{lesson.progress.status.replace('_', ' ')}</div>
              </div>
            ))}
            {lessons.length === 0 && <p className="text-sm text-muted">No lessons have been added yet.</p>}
          </div>
        ) : (
          <p className="text-sm text-muted">Select a course to review its lessons and learner progress state.</p>
        )}
      </Card>
    </div>
  );
}

function Certificates({ certificates, loading, templates }) {
  if (loading) {
    return <SkeletonRows />;
  }

  return (
    <div className="space-y-5">
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
      <Card title="Certificate templates">
        <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          {templates.map((template) => (
            <div className="rounded-control border border-line p-3" key={template.id}>
              <div className="text-sm font-medium text-ink">{template.name}</div>
              <div className="text-sm text-muted">{template.is_default ? 'Default template' : 'Custom template'}</div>
            </div>
          ))}
          {templates.length === 0 && <p className="text-sm text-muted">Create templates through the certificate template API.</p>}
        </div>
      </Card>
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
