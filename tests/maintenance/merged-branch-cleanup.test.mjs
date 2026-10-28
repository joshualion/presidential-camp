import assert from 'node:assert/strict';
import test from 'node:test';
import { cleanupMergedBranches } from '../../scripts/cleanup-merged-branches.mjs';

function fixture({ moved = false, newlyOpened = false } = {}) {
  const deleted = [];
  const repository = 'owner/repo';
  const head = (ref, sha) => ({ ref, sha, repo: { full_name: repository } });
  const api = async (path, options = {}) => {
    if (options.method === 'DELETE') { deleted.push(path); return null; }
    if (path === '/repos/owner/repo') return { default_branch: 'main' };
    if (path.includes('/branches?')) return ['main','protected','open','squashed','contained','unmerged'].map(name => ({ name, protected: name === 'protected', commit: { sha: name+'-sha' } }));
    if (path.includes('/pulls?state=closed')) return [{ number: 12, merged_at: '2026-10-28', head: head('squashed','squashed-sha') }];
    if (path.includes('/pulls?state=open&head=')) return newlyOpened ? [{ head: head('squashed','squashed-sha') }] : [];
    if (path.includes('/pulls?state=open')) return [{ head: head('open','open-sha') }];
    if (path.includes('/compare/')) return path.endsWith('contained-sha') ? { status:'behind', ahead_by:0 } : { status:'diverged', ahead_by:1 };
    if (path.includes('/git/ref/heads/')) { const name = path.split('/').at(-1); return { object: { sha: moved && name === 'squashed' ? 'new-work-sha' : name+'-sha' } }; }
    throw new Error('Unexpected API path '+path);
  };
  return { repository, api, deleted };
}

test('dry-run lists only proven merged branches without deleting anything', async () => {
  const f = fixture(); const r = await cleanupMergedBranches(f);
  assert.deepEqual(r.eligible.map(x=>x.name), ['squashed','contained']);
  assert.deepEqual(f.deleted, []);
});
test('deletion preserves default, protected, open-PR and unmerged work', async () => {
  const f = fixture(); const r = await cleanupMergedBranches({ ...f, dryRun:false });
  assert.deepEqual(r.deleted.map(x=>x.name), ['squashed','contained']);
  assert.equal(f.deleted.length, 2);
});
test('a reused branch whose head moved after inventory is preserved', async () => {
  const f = fixture({ moved:true }); const r = await cleanupMergedBranches({ ...f, dryRun:false });
  assert.deepEqual(r.deleted.map(x=>x.name), ['contained']);
  assert(r.skipped.some(x=>x.name==='squashed' && x.reason==='branch head changed'));
});
test('newly opened work is rechecked immediately before deletion', async () => {
  const f = fixture({ newlyOpened:true }); const r = await cleanupMergedBranches({ ...f, dryRun:false });
  assert.deepEqual(r.deleted, []);
});
test('uncertain ancestry fails closed and never deletes a branch', async () => {
  const f = fixture(); const api = async (...args) => args[0].includes('/compare/') ? { ahead_by:0 } : f.api(...args);
  const r = await cleanupMergedBranches({ ...f, api, dryRun:false });
  assert.deepEqual(r.deleted.map(x=>x.name), ['squashed']);
});
