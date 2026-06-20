module.exports = {
  content: ['./assets/src/**/*.{js,jsx,ts,tsx}', './templates/**/*.php'],
  theme: {
    extend: {
      colors: {
        ink: '#172033',
        muted: '#64748b',
        canvas: '#f7f9fc',
        panel: '#ffffff',
        line: '#dbe3ef',
        brand: '#2563eb',
        success: '#16a34a',
        warning: '#d97706',
        danger: '#dc2626',
        accent: '#7c3aed'
      },
      borderRadius: {
        control: '8px'
      }
    }
  },
  plugins: []
};
