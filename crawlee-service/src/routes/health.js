import { Router } from 'express';

const router = Router();

/**
 * Health check endpoint
 */
router.get('/', (req, res) => {
  res.json({
    success: true,
    status: 'healthy',
    service: 'crawlee-service',
    version: '1.0.0',
    timestamp: new Date().toISOString()
  });
});

/**
 * Detailed health check with system info
 */
router.get('/detailed', (req, res) => {
  const memUsage = process.memoryUsage();

  res.json({
    success: true,
    status: 'healthy',
    service: 'crawlee-service',
    version: '1.0.0',
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
    memory: {
      heapUsed: Math.round(memUsage.heapUsed / 1024 / 1024) + 'MB',
      heapTotal: Math.round(memUsage.heapTotal / 1024 / 1024) + 'MB',
      rss: Math.round(memUsage.rss / 1024 / 1024) + 'MB'
    },
    node: process.version,
    platform: process.platform
  });
});

export default router;
