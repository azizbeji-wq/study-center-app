import cors from 'cors';
import express from 'express';
import path from 'path';
import { fileURLToPath } from 'url';
import { academicRouter } from './server/routes/academic';
import { aiRouter } from './server/routes/ai';
import { authAndUsersRouter } from './server/routes/authAndUsers';
import { financeAndSchoolsRouter } from './server/routes/financeAndSchools';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function startServer() {
  const app = express();
  const PORT = Number(process.env.PORT) || 3000;

  app.use(cors());
  app.use(express.json({ limit: '10mb' }));
  app.use(express.urlencoded({ extended: true }));

  // Mount API routes
  app.use('/api', authAndUsersRouter);
  app.use('/api', academicRouter);
  app.use('/api', financeAndSchoolsRouter);
  app.use('/api', aiRouter);

  // Return JSON 404 for unknown /api/* routes
  app.use('/api', (_req, res) => {
    res.status(404).json({ message: 'Route API introuvable.' });
  });

  if (process.env.NODE_ENV !== 'production') {
    const { createServer: createViteServer } = await import('vite');
    const vite = await createViteServer({
      configFile: path.resolve(__dirname, 'frontend/vite.config.ts'),
      root: path.resolve(__dirname, 'frontend'),
      server: {
        middlewareMode: true,
        hmr: false,
      },
      appType: 'spa',
    });
    app.use(vite.middlewares);
  } else {
    const distDir = path.resolve(__dirname, 'frontend/dist');
    app.use(express.static(distDir));
    app.get('*', (_req, res) => {
      res.sendFile(path.join(distDir, 'index.html'));
    });
  }

  app.listen(PORT, '0.0.0.0', () => {
    console.log(`Server listening on http://0.0.0.0:${PORT}`);
  });
}

startServer().catch((err) => {
  console.error('Failed to start server:', err);
  process.exit(1);
});
