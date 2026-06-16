import { useState } from 'react';
import axios from 'axios';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { CopyIcon, RefreshCwIcon } from 'lucide-react';
import { toast } from 'sonner';

type SiteOption = { id: number; domain: string };

type IntegrationDetails = { endpoint: string; token: string };

type ErrorIntegrationDialogProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  serverId: number;
  sites: SiteOption[];
};

function laravelSnippet({ endpoint, token }: IntegrationDetails): string {
  return `# .env
VITO_ERROR_REPORTER_ENDPOINT=${endpoint}
VITO_ERROR_REPORTER_TOKEN=${token}
VITO_ERROR_REPORTER_RELEASE=v1.0.0

# Requires the VitoErrorReporter SDK class in your app.
# In a service provider's boot():
\\App\\SDK\\VitoErrorReporter::registerGlobalHandler();`;
}

function expressSnippet({ endpoint, token }: IntegrationDetails): string {
  return `npm i @davismw/vito-error-reporter

# .env
VITO_ERROR_REPORTER_ENDPOINT=${endpoint}
VITO_ERROR_REPORTER_TOKEN=${token}

// Mount AFTER your routes:
const { vitoErrorMiddleware } = require('@davismw/vito-error-reporter/express');
app.use(vitoErrorMiddleware());`;
}

function nodeSnippet({ endpoint, token }: IntegrationDetails): string {
  return `npm i @davismw/vito-error-reporter

# .env
VITO_ERROR_REPORTER_ENDPOINT=${endpoint}
VITO_ERROR_REPORTER_TOKEN=${token}

const { VitoErrorReporter } = require('@davismw/vito-error-reporter');
VitoErrorReporter.fromEnv().installGlobalHandlers();`;
}

function curlSnippet({ endpoint, token }: IntegrationDetails): string {
  return `curl -X POST ${endpoint} \\
  -H "Authorization: Bearer ${token}" \\
  -H "Content-Type: application/json" \\
  -d '{
    "exception_class": "RuntimeException",
    "message": "Something broke",
    "stack_trace": "...",
    "environment": "production",
    "release": "v1.0.0"
  }'`;
}

const PLATFORMS: { value: string; label: string; build: (d: IntegrationDetails) => string }[] = [
  { value: 'laravel', label: 'Laravel', build: laravelSnippet },
  { value: 'express', label: 'Node · Express', build: expressSnippet },
  { value: 'node', label: 'Node', build: nodeSnippet },
  { value: 'curl', label: 'cURL', build: curlSnippet },
];

export default function ErrorIntegrationDialog({ open, onOpenChange, serverId, sites }: ErrorIntegrationDialogProps) {
  const [siteId, setSiteId] = useState<string>('');
  const [details, setDetails] = useState<IntegrationDetails | null>(null);
  const [loading, setLoading] = useState(false);

  const loadToken = async (id: string) => {
    setSiteId(id);
    setDetails(null);
    if (!id) {
      return;
    }
    setLoading(true);
    try {
      const response = await axios.get<IntegrationDetails>(route('errors.integration', { server: serverId, site: Number(id) }));
      setDetails(response.data);
    } catch {
      toast.error('Failed to load ingestion token');
    } finally {
      setLoading(false);
    }
  };

  const regenerate = async () => {
    if (!siteId) {
      return;
    }
    setLoading(true);
    try {
      const response = await axios.post<IntegrationDetails>(route('errors.integration.regenerate', { server: serverId, site: Number(siteId) }));
      setDetails(response.data);
      toast.success('Token regenerated');
    } catch {
      toast.error('Failed to regenerate token');
    } finally {
      setLoading(false);
    }
  };

  const copy = async (text: string, label: string) => {
    try {
      await navigator.clipboard.writeText(text);
      toast.success(`${label} copied`);
    } catch {
      toast.error('Failed to copy');
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>Connect an application</DialogTitle>
          <DialogDescription>Select a site to reveal its ingestion endpoint and token, then follow the setup for your platform.</DialogDescription>
        </DialogHeader>

        <div className="flex flex-col gap-4">
          <Select value={siteId} onValueChange={loadToken}>
            <SelectTrigger>
              <SelectValue placeholder="Select a site" />
            </SelectTrigger>
            <SelectContent>
              {sites.map((site) => (
                <SelectItem key={site.id} value={String(site.id)}>
                  {site.domain}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          {loading && <p className="text-sm text-muted-foreground">Loading…</p>}

          {details && (
            <div className="flex flex-col gap-3">
              <div>
                <p className="mb-1 text-sm font-medium">Endpoint</p>
                <div className="flex items-center gap-2">
                  <code className="flex-1 rounded bg-muted px-2 py-1 text-xs break-all">{details.endpoint}</code>
                  <Button variant="ghost" size="icon" aria-label="Copy endpoint" onClick={() => copy(details.endpoint, 'Endpoint')}>
                    <CopyIcon className="h-3 w-3" />
                  </Button>
                </div>
              </div>
              <div>
                <p className="mb-1 text-sm font-medium">Token</p>
                <div className="flex items-center gap-2">
                  <code className="flex-1 rounded bg-muted px-2 py-1 text-xs break-all">{details.token}</code>
                  <Button variant="ghost" size="icon" aria-label="Copy token" onClick={() => copy(details.token, 'Token')}>
                    <CopyIcon className="h-3 w-3" />
                  </Button>
                </div>
                <p className="mt-1 text-xs text-muted-foreground">Per-site secret — store it in the app's env, never in client-side code.</p>
              </div>

              <div>
                <p className="mb-1 text-sm font-medium">Setup</p>
                <Tabs defaultValue="laravel">
                  <TabsList>
                    {PLATFORMS.map((platform) => (
                      <TabsTrigger key={platform.value} value={platform.value}>
                        {platform.label}
                      </TabsTrigger>
                    ))}
                  </TabsList>
                  {PLATFORMS.map((platform) => {
                    const code = platform.build(details);
                    return (
                      <TabsContent key={platform.value} value={platform.value}>
                        <div className="relative">
                          <pre className="max-h-64 overflow-auto rounded-lg bg-muted p-3 pr-10 text-xs whitespace-pre-wrap break-all">{code}</pre>
                          <Button
                            variant="ghost"
                            size="icon"
                            className="absolute top-1.5 right-1.5"
                            aria-label="Copy setup snippet"
                            onClick={() => copy(code, `${platform.label} setup`)}
                          >
                            <CopyIcon className="h-3 w-3" />
                          </Button>
                        </div>
                      </TabsContent>
                    );
                  })}
                </Tabs>
              </div>

              <div>
                <Button variant="outline" size="sm" disabled={loading} onClick={regenerate}>
                  <RefreshCwIcon className="h-3 w-3" />
                  Regenerate
                </Button>
              </div>
            </div>
          )}
        </div>

        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
