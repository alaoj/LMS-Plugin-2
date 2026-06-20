export function Button({ children, variant = 'primary', ...props }) {
  const classes = {
    primary: 'bg-brand text-white hover:bg-blue-700',
    secondary: 'border border-line bg-panel text-ink hover:bg-slate-50',
    ghost: 'text-muted hover:bg-slate-100 hover:text-ink',
    danger: 'bg-danger text-white hover:bg-red-700'
  };

  return (
    <button
      className={`inline-flex min-h-10 items-center justify-center gap-2 rounded-control px-4 text-sm font-medium transition ${classes[variant]}`}
      {...props}
    >
      {children}
    </button>
  );
}
