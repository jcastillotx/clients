import express from 'express';
import helmet from 'helmet';
import cors from 'cors';
import { config } from 'dotenv';
import { logger } from './utils/logger.js';
import { apiKeyAuth } from './middleware/auth.js';
import crawlerRoutes from './routes/crawler.js';
import healthRoutes from './routes/health.js';

// Load environment variables
config();

const app = express();
const PORT = process.env.PORT || 3001;
const HOST = process.env.HOST || '127.0.0.1';

// Middleware
app.use(helmet());
app.use(cors({
  origin: process.env.CORS_ORIGIN || '*',
  methods: ['GET', 'POST'],
  allowedHeaders: ['Content-Type', 'X-API-Key', 'Authorization']
}));
app.use(express.json({ limit: '10mb' }));

// Request logging
app.use((req, res, next) => {
  const start = Date.now();
  res.on('finish', () => {
    const duration = Date.now() - start;
    logger.info(`${req.method} ${req.path} ${res.statusCode} ${duration}ms`);
  });
  next();
});

// Health check routes (no auth required)
app.use('/health', healthRoutes);

// API routes (auth required)
app.use('/api/v1', apiKeyAuth, crawlerRoutes);

// Error handling
app.use((err, req, res, next) => {
  logger.error('Unhandled error:', err);
  res.status(500).json({
    success: false,
    error: process.env.NODE_ENV === 'production'
      ? 'Internal server error'
      : err.message
  });
});

// 404 handler
app.use((req, res) => {
  res.status(404).json({
    success: false,
    error: 'Endpoint not found'
  });
});

// Start server
app.listen(PORT, HOST, () => {
  logger.info(`Crawlee service running at http://${HOST}:${PORT}`);
  logger.info(`Environment: ${process.env.NODE_ENV || 'development'}`);
});

// Graceful shutdown
process.on('SIGTERM', () => {
  logger.info('SIGTERM received, shutting down gracefully');
  process.exit(0);
});

process.on('SIGINT', () => {
  logger.info('SIGINT received, shutting down gracefully');
  process.exit(0);
});

export default app;
