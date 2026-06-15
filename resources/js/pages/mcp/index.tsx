import { Head, usePage } from '@inertiajs/react';
import SettingsLayout from '@/layouts/settings/layout';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { CopyIcon, ExternalLinkIcon, TerminalIcon, ServerIcon } from 'lucide-react';
import { toast } from 'sonner';

type Page = {
  apiUrl: string;
  healthEndpoint: string;
};

export default function McpSettings() {
  const page = usePage<Page>();
  const { apiUrl, healthEndpoint } = page.props;

  const copyToClipboard = async (text: string, label: string) => {
    try {
      await navigator.clipboard.writeText(text);
      toast.success(`${label} copied`);
    } catch {
      toast.error('Failed to copy');
    }
  };

  return (
    <SettingsLayout>
      <Head title="MCP - Settings" />
      <Container className="max-w-4xl">
        <Heading
          title="MCP (Model Context Protocol)"
          description="Connect AI agents to VitoDeploy for autonomous server management and diagnostics"
        />

        <div className="space-y-6 mt-6">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <TerminalIcon className="h-5 w-5" />
                Quick Setup
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <p className="text-sm text-muted-foreground mb-2">
                  Add this to your AI client's <code className="bg-muted px-1 rounded">.mcp.json</code>:
                </p>
                <pre className="bg-muted p-4 rounded-lg text-xs overflow-x-auto">
{`{
  "mcpServers": {
    "vito-sre": {
      "command": "node",
      "args": ["dist/index.js"],
      "env": {
        "VITO_API_URL": "${apiUrl}",
        "VITO_API_TOKEN": "your-api-token-here",
        "VITO_MCP_MEMORY_PATH": "~/.vito-mcp/memory.db"
      }
    }
  }
}`}
                </pre>
                <div className="flex gap-2 mt-2">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => copyToClipboard(
                      `{\n  "mcpServers": {\n    "vito-sre": {\n      "command": "node",\n      "args": ["dist/index.js"],\n      "env": {\n        "VITO_API_URL": "${apiUrl}",\n        "VITO_API_TOKEN": "your-api-token-here",\n        "VITO_MCP_MEMORY_PATH": "~/.vito-mcp/memory.db"\n      }\n    }\n  }\n}`,
                      'Config'
                    )}
                  >
                    <CopyIcon className="h-3 w-3" />
                    Copy Config
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <ServerIcon className="h-5 w-5" />
                Connection Details
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex items-center justify-between py-2 border-b">
                <span className="text-sm font-medium">API URL</span>
                <div className="flex items-center gap-2">
                  <code className="text-sm bg-muted px-2 py-0.5 rounded">{apiUrl}</code>
                  <Button variant="ghost" size="icon" onClick={() => copyToClipboard(apiUrl, 'API URL')}>
                    <CopyIcon className="h-3 w-3" />
                  </Button>
                </div>
              </div>
              <div className="flex items-center justify-between py-2 border-b">
                <span className="text-sm font-medium">Health Endpoint</span>
                <code className="text-sm bg-muted px-2 py-0.5 rounded">{healthEndpoint}</code>
              </div>
              <div className="flex items-center justify-between py-2">
                <span className="text-sm font-medium">API Token</span>
                <Button variant="outline" size="sm" asChild>
                  <a href={route('api-keys')}>
                    Manage API Keys
                    <ExternalLinkIcon className="h-3 w-3 ml-1" />
                  </a>
                </Button>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Available Tools</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 gap-3 text-sm">
                <div className="space-y-1">
                  <p className="font-medium">Infrastructure</p>
                  <p className="text-muted-foreground text-xs">list_servers, get_server, get_server_metrics, get_server_services, get_server_firewall, reboot_server</p>
                </div>
                <div className="space-y-1">
                  <p className="font-medium">Applications</p>
                  <p className="text-muted-foreground text-xs">list_apps, deploy_app, rollback_app, restart_app, get_app_health, get_deployment_history</p>
                </div>
                <div className="space-y-1">
                  <p className="font-medium">Databases</p>
                  <p className="text-muted-foreground text-xs">list_databases, create_database, database_health, database_metrics</p>
                </div>
                <div className="space-y-1">
                  <p className="font-medium">SSL & Domains</p>
                  <p className="text-muted-foreground text-xs">verify_ssl, renew_ssl, ssl_expiration_report, verify_dns</p>
                </div>
                <div className="space-y-1">
                  <p className="font-medium">Intelligence</p>
                  <p className="text-muted-foreground text-xs">investigate_incident, analyze_failed_deployment, audit_infrastructure, audit_security</p>
                </div>
                <div className="space-y-1">
                  <p className="font-medium">Autonomous Ops</p>
                  <p className="text-muted-foreground text-xs">safe_deploy, smart_rollback, recover_service, generate_runbook, generate_postmortem</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </Container>
    </SettingsLayout>
  );
}
