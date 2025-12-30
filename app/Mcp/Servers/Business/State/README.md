# State Directory

This directory is used for persistent state management across MCP tool invocations.

## Purpose

Based on Anthropic's MCP best practices, this directory enables:

- **Computation Caching**: Store results of expensive operations
- **Progress Tracking**: Maintain state for long-running business processes
- **Agent Learning**: Store patterns and insights discovered by agents
- **Data Persistence**: Save intermediate results between tool calls

## Usage

State files can be JSON, serialized PHP, or any format suitable for your needs.

Common patterns:

```php
// Save state
file_put_contents(__DIR__ . '/cache.json', json_encode($data));

// Load state
$data = json_decode(file_get_contents(__DIR__ . '/cache.json'), true);
```

## Security

- Never commit sensitive data (credentials, API keys, PII)
- Add `.gitignore` entries for sensitive state files
- Clean up temporary state regularly
- Validate data when loading from state files
