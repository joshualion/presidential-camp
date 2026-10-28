import { pathToFileURL } from 'node:url';

// A merged PR (including squash merges) or full ancestry containment is required.
// Never delete default/protected branches, open-PR work, or a branch whose head moved.
export async function cleanupMergedBranches({ repository, api, dryRun = true }) {
  if (!/^[\w.-]+\/[\w.-]+$/.test(repository)) throw new Error('A single repository is required.');
  const prefix = `/repos/${repository}`;
  const list = async endpoint => {
    const result = [];
    for (let page = 1; ; page++) {
      const data = await api(`${prefix}/${endpoint}${endpoint.includes('?') ? '&' : '?'}per_page=100&page=${page}`);
      if (!Array.isArray(data)) throw new Error('Unexpected GitHub list response.');
      result.push(...data);
      if (data.length < 100) return result;
    }
  };
  const repo = await api(prefix);
  if (!repo.default_branch) throw new Error('The default branch could not be established.');
  const open = await list('pulls?state=open');
  const closed = await list('pulls?state=closed');
  const branches = await list('branches');
  const sameRepo = pr => pr.head?.repo?.full_name === repository;
  const openHeads = new Set(open.filter(sameRepo).map(pr => pr.head.ref));
  const merged = closed.filter(pr => pr.merged_at && sameRepo(pr));
  const report = { repository, dryRun, defaultBranch: repo.default_branch, deleted: [], eligible: [], skipped: [] };
  for (const branch of branches) {
    const name = branch.name;
    const sha = branch.commit?.sha;
    const skip = reason => report.skipped.push({ name, reason });
    if (!name || !sha || name === repo.default_branch || name === 'main' || branch.protected) { skip('default, protected, or incomplete branch'); continue; }
    if (openHeads.has(name)) { skip('open pull request'); continue; }
    const pr = merged.find(pr => pr.head.ref === name && pr.head.sha === sha);
    if (!pr) {
      const comparison = await api(`${prefix}/compare/${encodeURIComponent(repo.default_branch)}...${encodeURIComponent(sha)}`);
      if (!['behind', 'identical'].includes(comparison.status) || comparison.ahead_by !== 0) { skip('unmerged commits'); continue; }
    }
    const encoded = name.split('/').map(encodeURIComponent).join('/');
    const current = await api(`${prefix}/git/ref/heads/${encoded}`);
    if (current.object?.sha !== sha) { skip('branch head changed'); continue; }
    const active = await api(`${prefix}/pulls?state=open&head=${encodeURIComponent(repository.split('/')[0]+':'+name)}&per_page=100`);
    if (!Array.isArray(active) || active.some(sameRepo)) { skip('open pull request or uncertain state'); continue; }
    const entry = { name, sha, reason: pr ? `merged PR #${pr.number}` : 'fully contained in default branch' };
    report.eligible.push(entry);
    if (!dryRun) {
      await api(`${prefix}/git/refs/heads/${encoded}`, { method: 'DELETE' });
      report.deleted.push(entry);
    }
  }
  return report;
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  const token = process.env.GITHUB_TOKEN;
  if (!token) throw new Error('The scoped workflow token is required.');
  const api = async (pathname, { method = 'GET' } = {}) => {
    const response = await fetch('https://api.github.com'+pathname, { method, redirect: 'error', headers: {
      Authorization: `Bearer ${token}`, Accept: 'application/vnd.github+json', 'X-GitHub-Api-Version': '2022-11-28',
    } });
    if (!response.ok) throw new Error(`GitHub ${method} ${pathname} returned ${response.status}; no forced deletion will be attempted.`);
    return response.status === 204 ? null : response.json();
  };
  const result = await cleanupMergedBranches({ repository: process.env.GITHUB_REPOSITORY, api, dryRun: process.env.CLEANUP_DRY_RUN !== 'false' });
  console.log(JSON.stringify(result, null, 2));
}
