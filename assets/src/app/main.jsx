import { createRoot } from 'react-dom/client';
import '../styles/app.css';
import { App } from './App';

document.querySelectorAll('.zadora-lms-root').forEach((element) => {
  createRoot(element).render(<App initialView={element.dataset.view || 'dashboard'} />);
});
