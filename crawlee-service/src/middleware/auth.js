import { logger } from '../utils/logger.js';

/**
 * API Key authentication middleware
 */
export const apiKeyAuth = (req, res, next) => {
  const apiKey = process.env.API_KEY;

  // Skip auth if no API key is configured (development mode)
  if (!apiKey) {
    logger.warn('No API_KEY configured - authentication disabled');
    return next();
  }

  const providedKey = req.headers['x-api-key'] || req.headers['authorization']?.replace('Bearer ', '');

  if (!providedKey) {
    return res.status(401).json({
      success: false,
      error: 'API key required'
    });
  }

  if (providedKey !== apiKey) {
    logger.warn(`Invalid API key attempt from ${req.ip}`);
    return res.status(403).json({
      success: false,
      error: 'Invalid API key'
    });
  }

  next();
};

export default apiKeyAuth;
