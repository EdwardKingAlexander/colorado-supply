# State Directory

This directory is used for persistent state management across MCP tool invocations.

## Purpose

Based on Anthropic's MCP best practices, this directory enables:

- **Session Persistence**: Store browser cookies, authentication tokens, and session data
- **Caching**: Save frequently accessed data to reduce redundant operations
- **Progress Tracking**: Maintain state for long-running multi-step operations
- **Agent Learning**: Store patterns and insights discovered by agents

## State Files

### session.json
Created by: `VendorPortalLoginTool`

Stores authentication cookies and login metadata for reuse across tools.

Structure:
```json
{
  "login_url": "https://vendor.com/login",
  "current_url": "https://vendor.com/dashboard",
  "username": "account@company.com",
  "cookies": { ... },
  "logged_in_at": "2025-11-09T10:30:00+00:00"
}
```

### scrape-progress.json
Created by: `MultiPageScrapeSkill`

Tracks progress for multi-page scraping operations, enabling resume after interruption.

Structure:
```json
{
  "total_urls": 100,
  "processed_count": 45,
  "success_count": 42,
  "error_count": 3,
  "last_updated": "2025-11-09T11:15:00+00:00",
  "results": { ... }
}
```

### html-cache/
Directory containing raw HTML snapshots from scraping operations.

## Usage

State files can be JSON, serialized PHP, or any format suitable for your needs.

Common patterns:

```php
// Save state
file_put_contents(__DIR__ . '/session.json', json_encode($sessionData));

// Load state
$sessionData = json_decode(file_get_contents(__DIR__ . '/session.json'), true);
```

## Security

- Never commit sensitive data (credentials, API keys, tokens)
- Add `.gitignore` entries for sensitive state files
- Clean up temporary state regularly
- Validate data when loading from state files
