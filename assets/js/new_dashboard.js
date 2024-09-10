import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Dashboard from '../react/controllers/Dashboard';
import PageNotFound from "../react/controllers/PageNotFound";
import { ThemeProvider } from '../react/controllers/ThemenContext'; // Import ThemeProvider
import '../react/styles/new_dashboard.css';
import 'bootstrap/dist/js/bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const reactRootContainer = document.getElementById('react-root');
    if (reactRootContainer) {
        const root = createRoot(reactRootContainer);
        root.render(
            <ThemeProvider>
                <Router>
                    <Routes>
                        <Route path="/new" element={<Dashboard maxItems={4} />} />
                        <Route path="/new/alerts/:id?" element={<PageNotFound />} />
                        <Route path="/new/status/:id?" element={<PageNotFound />} />
                        <Route path="/new/performance/:id?" element={<PageNotFound />} />
                    </Routes>
                </Router>
            </ThemeProvider>
        );
    }
});
