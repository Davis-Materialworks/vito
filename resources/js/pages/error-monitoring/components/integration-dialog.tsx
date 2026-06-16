import { useState } from 'react';
import axios from 'axios';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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
          <DialogDescription>Select a site to reveal its ingestion endpoint and token, then point your app's error reporter at it.</DialogDescription>
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
