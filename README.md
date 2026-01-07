# Zabbix AI Problem Analysis Module

AI-powered problem analysis for Zabbix 7.0+ with support for multiple AI backends (Claude, OpenAI, Custom).

## Features

- 🤖 **AI-Powered Analysis**: Get intelligent insights on Zabbix problems
- 🔄 **Multiple AI Backends**: Support for Claude, OpenAI, and custom AI APIs
- 🎯 **Context-Aware**: Analyzes events with full context (history, related events, host info)
- 💡 **Actionable Insights**: Root cause analysis, troubleshooting steps, and prevention tips
- ⚡ **On-Demand**: Analysis generated when you need it
- 🔒 **Secure**: Uses user's own Zabbix permissions

## Installation

### 1. Module Structure

Create the following directory structure in your Zabbix frontend:

```
ui/modules/ai-problem-analysis/
├── manifest.json
├── Module.php
├── actions/
│   ├── Popup.php
│   └── Analyze.php
├── Services/
│   ├── AIAnalyzer.php
│   └── Backends/
│       ├── AIBackendInterface.php
│       ├── ClaudeBackend.php
│       ├── OpenAIBackend.php
│       └── CustomBackend.php
├── views/
│   └── popup.php
├── assets/
│   ├── js/
│   │   └── ai-analysis.js
│   └── css/
│       └── ai-analysis.css
└── README.md
```

### 2. Copy Files

Copy all the provided files to their respective locations in the module directory.

### 3. Set Permissions

```bash
chown -R www-data:www-data ui/modules/ai-problem-analysis
chmod -R 755 ui/modules/ai-problem-analysis
```

### 4. Enable Module

1. Log in to Zabbix as an administrator
2. Go to **Administration → General → Modules**
3. Click **Scan directory**
4. Find "AI Problem Analysis" and click **Enable**
5. Configure your AI backend settings (see Configuration section)

### 5. Include Assets

Edit your Zabbix frontend configuration to include the module's JavaScript and CSS:

**Option A: Via Module Hook (Recommended)**

Add to `Module.php` in the `init()` method:

```php
public function init(): void {
    // Add CSS
    APP::Component()->get('page.assets')
        ->addCssFile('modules/ai-problem-analysis/assets/css/ai-analysis.css');
    
    // Add JavaScript
    APP::Component()->get('page.assets')
        ->addJavaScriptFile('modules/ai-problem-analysis/assets/js/ai-analysis.js');
}
```

**Option B: Manual Include**

Add to `include/page_footer.php` or your theme's footer:

```php
echo '<link rel="stylesheet" href="modules/ai-problem-analysis/assets/css/ai-analysis.css">';
echo '<script src="modules/ai-problem-analysis/assets/js/ai-analysis.js"></script>';
```

## Configuration

### Claude (Anthropic)

1. Get API key from https://console.anthropic.com/
2. In module settings, set:
   - `ai_backend`: `claude`
   - `anthropic_api_key`: Your API key
   - `claude_model`: `claude-sonnet-4-20250514` (or other model)

### OpenAI

1. Get API key from https://platform.openai.com/
2. In module settings, set:
   - `ai_backend`: `openai`
   - `openai_api_key`: Your API key
   - `openai_model`: `gpt-4` or `gpt-4-turbo`

### Custom AI Backend

For local LLMs (Ollama, LM Studio, etc.) or other APIs:

1. In module settings, set:
   - `ai_backend`: `custom`
   - `custom_api_url`: Your API endpoint
   - `custom_api_key`: Your API key (if required)

**Example for Ollama:**
```json
{
    "ai_backend": "custom",
    "custom_api_url": "http://localhost:11434/api/generate",
    "custom_api_key": ""
}
```

### Configuration Options

| Option | Description | Default |
|--------|-------------|---------|
| `ai_backend` | AI backend to use (`claude`, `openai`, `custom`) | `claude` |
| `anthropic_api_key` | Claude API key | `` |
| `claude_model` | Claude model name | `claude-sonnet-4-20250514` |
| `openai_api_key` | OpenAI API key | `` |
| `openai_model` | OpenAI model name | `gpt-4` |
| `custom_api_url` | Custom API endpoint | `` |
| `custom_api_key` | Custom API key | `` |
| `analysis_timeout` | API request timeout (seconds) | `30` |
| `include_history_hours` | Hours of history to include | `24` |
| `cache_duration` | Cache duration (seconds) | `300` |

## Usage

1. Go to **Monitoring → Problems**
2. Click the 🤖 (robot) button next to any problem
3. Wait for AI analysis (usually 5-15 seconds)
4. Review the analysis with:
   - Root cause analysis
   - Immediate actions to take
   - Step-by-step resolution
   - Prevention measures
   - Severity assessment

## Troubleshooting

### Module not appearing

- Check file permissions
- Verify directory structure matches exactly
- Check Zabbix frontend logs: `/var/log/zabbix/zabbix_server.log`
- Ensure PHP version >= 7.4

### AI Analysis button not showing

- Clear browser cache
- Check JavaScript console for errors
- Verify `ai-analysis.js` is loaded
- Check that you're on the Problems page

### API errors

- **Claude**: Verify API key at https://console.anthropic.com/
- **OpenAI**: Check API key and billing at https://platform.openai.com/
- **Custom**: Test API endpoint with curl:
  ```bash
  curl -X POST http://localhost:11434/api/generate \
    -H "Content-Type: application/json" \
    -d '{"prompt": "test"}'
  ```

### No analysis results

- Check PHP error logs: `/var/log/php/error.log`
- Enable debug mode in Zabbix
- Verify API keys are correct
- Check network connectivity to AI APIs
- Review `analysis_timeout` setting

## Development

### Adding a New AI Backend

1. Create new class in `Services/Backends/YourBackend.php`
2. Implement `AIBackendInterface`
3. Add to `AIAnalyzer` constructor:
   ```php
   $this->backends['yourbackend'] = new Backends\YourBackend($config);
   ```
4. Add configuration options to `manifest.json`

### Customizing the Prompt

Edit `buildPrompt()` method in `Services/AIAnalyzer.php` to customize what information is sent to the AI.

### Styling

Modify `assets/css/ai-analysis.css` to match your Zabbix theme.

## Security Considerations

- API keys are stored in module configuration (ensure proper file permissions)
- All API requests use HTTPS
- User permissions are checked before displaying analysis
- No sensitive data is logged (except in debug mode)
- Consider using environment variables for API keys in production

## Performance

- Analysis is on-demand (not pre-cached)
- Average response time: 5-15 seconds
- Backend fallback ensures availability
- Adjust `analysis_timeout` based on your needs

## API Costs

Be aware of API costs when using cloud AI services:

- **Claude**: ~$0.003-0.015 per analysis
- **OpenAI GPT-4**: ~$0.01-0.03 per analysis
- **Custom/Local**: Free (requires own infrastructure)

## Contributing

Contributions welcome! Please submit issues and pull requests to the GitHub repository.

## License

[Your chosen license]

## Support

For issues and questions:
- GitHub Issues: [your-repo-url]
- Zabbix Forum: [forum-thread]
- Email: [your-email]

## Changelog

### Version 1.0.0
- Initial release
- Support for Claude, OpenAI, and custom backends
- Comprehensive event analysis
- Modal popup interface
