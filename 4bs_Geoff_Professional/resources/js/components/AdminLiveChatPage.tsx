import { ChangeEvent, FormEvent, useCallback, useEffect, useRef, useState } from 'react';
import { api } from '../api';

type Status = 'ai' | 'waiting_for_admin' | 'live' | 'closed';

type ConversationListItem = {
  id: number;
  status: Status;
  subject: string;
  user: { id: number; name: string; email: string; avatarUrl: string | null };
  assignedAdmin: { id: number; name: string } | null;
  messageCount: number;
  lastMessage: string | null;
  lastMessageAt: string | null;
};

type Message = {
  id: number;
  senderType: 'user' | 'ai' | 'admin' | 'system';
  senderId: number | null;
  body: string;
  createdAt: string | null;
};

type DetailResponse = { conversation: ConversationListItem; messages: Message[] };
type Props = { adminId: number; adminName: string };

const labels: Record<Status, string> = {
  ai: 'AI active',
  waiting_for_admin: 'Waiting',
  live: 'Live',
  closed: 'Closed',
};

export default function AdminLiveChatPage({ adminId, adminName }: Props) {
  const [conversations, setConversations] = useState<ConversationListItem[]>([]);
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [detail, setDetail] = useState<DetailResponse | null>(null);
  const [draft, setDraft] = useState('');
  const [filter, setFilter] = useState<'active' | 'all'>('active');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const messageEndRef = useRef<HTMLDivElement | null>(null);
  const listLoadingRef = useRef(false);
  const detailLoadingRef = useRef(false);

  const loadConversations = useCallback(async () => {
    if (listLoadingRef.current) return;
    listLoadingRef.current = true;
    try {
      const data = await api<{ conversations: ConversationListItem[] }>('/admin/chat/conversations');
      setConversations(data.conversations);
      setSelectedId((current) => current ?? data.conversations[0]?.id ?? null);
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Conversations could not be loaded.');
    } finally {
      listLoadingRef.current = false;
    }
  }, []);

  const loadDetail = useCallback(async (id: number) => {
    if (detailLoadingRef.current) return;
    detailLoadingRef.current = true;
    try {
      const data = await api<DetailResponse>(`/admin/chat/conversations/${id}`);
      setDetail(data);
      setError('');
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Conversation could not be loaded.');
    } finally {
      detailLoadingRef.current = false;
    }
  }, []);

  useEffect(() => {
    void loadConversations();
    const timer = window.setInterval(() => void loadConversations(), 3000);
    return () => window.clearInterval(timer);
  }, [loadConversations]);

  useEffect(() => {
    if (!selectedId) {
      setDetail(null);
      return;
    }
    void loadDetail(selectedId);
    const timer = window.setInterval(() => void loadDetail(selectedId), 1500);
    return () => window.clearInterval(timer);
  }, [selectedId, loadDetail]);

  useEffect(() => {
    messageEndRef.current?.scrollIntoView({ block: 'nearest' });
  }, [detail?.messages.length]);

  async function action(endpoint: string) {
    if (!selectedId || busy) return;
    setBusy(true);
    setError('');
    try {
      await api(endpoint, { method: 'POST' });
      await Promise.all([loadConversations(), loadDetail(selectedId)]);
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'The action could not be completed.');
    } finally {
      setBusy(false);
    }
  }

  async function send(event: FormEvent) {
    event.preventDefault();
    if (!selectedId || !draft.trim() || busy) return;
    setBusy(true);
    try {
      await api(`/admin/chat/conversations/${selectedId}/messages`, {
        method: 'POST',
        body: JSON.stringify({ message: draft.trim() }),
      });
      setDraft('');
      await loadDetail(selectedId);
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Message could not be sent.');
    } finally {
      setBusy(false);
    }
  }

  const visibleConversations = conversations.filter((conversation) => filter === 'all' || conversation.status !== 'closed');
  const selected = detail?.conversation;
  const isAssignedToMe = selected?.assignedAdmin?.id === adminId;
  const canReply = selected?.status === 'live' && isAssignedToMe;
  const waitingCount = conversations.filter((conversation) => conversation.status === 'waiting_for_admin').length;

  return (
    <section className="admin-chat-shell" aria-labelledby="live-chat-title">
      <header className="admin-chat-heading">
        <div><p className="eyebrow">Support operations</p><h1 id="live-chat-title">Conversation control center</h1><p>Review AI conversations, accept live-support requests, and assist customers without losing the audit trail.</p></div>
        <div className="queue-metric"><strong>{waitingCount}</strong><span>waiting for support</span></div>
      </header>

      <div className="admin-chat-grid">
        <aside className="conversation-panel" aria-label="Customer conversations">
          <div className="conversation-panel-header">
            <div><strong>Inbox</strong><small>{visibleConversations.length} conversations</small></div>
            <div className="segmented"><button className={filter === 'active' ? 'active' : ''} onClick={() => setFilter('active')}>Active</button><button className={filter === 'all' ? 'active' : ''} onClick={() => setFilter('all')}>All</button></div>
          </div>
          <div className="conversation-list">
            {visibleConversations.map((conversation) => (
              <button key={conversation.id} className={`conversation-item ${selectedId === conversation.id ? 'selected' : ''}`} onClick={() => setSelectedId(conversation.id)}>
                <div className="avatar avatar-small">{conversation.user.avatarUrl ? <img src={conversation.user.avatarUrl} alt="" /> : conversation.user.name.slice(0, 1).toUpperCase()}</div>
                <div className="conversation-preview">
                  <div><strong>{conversation.user.name}</strong><span className={`mini-status status-${conversation.status}`}>{labels[conversation.status]}</span></div>
                  <p>{conversation.lastMessage ?? 'No messages yet'}</p>
                  <small>{conversation.lastMessageAt ? new Date(conversation.lastMessageAt).toLocaleString() : conversation.user.email}</small>
                </div>
              </button>
            ))}
            {visibleConversations.length === 0 && <div className="panel-empty">No conversations in this view.</div>}
          </div>
        </aside>

        <main className="admin-thread-panel">
          {!selected || !detail ? (
            <div className="thread-empty"><div className="empty-chat-icon">4BS</div><h2>Select a conversation</h2><p>The complete user and AI transcript will appear here.</p></div>
          ) : (
            <>
              <header className="thread-header">
                <div className="identity-row">
                  <div className="avatar">{selected.user.avatarUrl ? <img src={selected.user.avatarUrl} alt="" /> : selected.user.name.slice(0, 1).toUpperCase()}</div>
                  <div><h2>{selected.user.name}</h2><p>{selected.user.email} · Conversation #{selected.id}</p></div>
                </div>
                <div className="thread-actions">
                  {selected.status === 'waiting_for_admin' && <button className="btn primary" onClick={() => action(`/admin/chat/conversations/${selected.id}/claim`)} disabled={busy}>Claim request</button>}
                  {selected.status === 'live' && !isAssignedToMe && <span className="assigned-admin">Assigned to {selected.assignedAdmin?.name}</span>}
                  {selected.status === 'live' && isAssignedToMe && <button className="btn secondary" onClick={() => action(`/admin/chat/conversations/${selected.id}/end`)} disabled={busy}>End live chat</button>}
                  {selected.status !== 'closed' && <button className="btn danger-outline" onClick={() => action(`/admin/chat/conversations/${selected.id}/close`)} disabled={busy}>Close</button>}
                </div>
              </header>

              <div className="admin-message-list" role="log" aria-live="polite">
                {detail.messages.map((message) => (
                  <article key={message.id} className={`message message-${message.senderType}`}>
                    <div className="message-meta"><strong>{message.senderType === 'user' ? selected.user.name : message.senderType === 'ai' ? '4BS AI' : message.senderType === 'admin' ? (message.senderId === adminId ? 'You' : 'Administrator') : 'System'}</strong>{message.createdAt && <time>{new Date(message.createdAt).toLocaleString()}</time>}</div>
                    <p>{message.body}</p>
                  </article>
                ))}
                <div ref={messageEndRef} />
              </div>

              {error && <div className="inline-error" role="alert">{error}</div>}
              <form className="admin-composer" onSubmit={send}>
                <textarea value={draft} onChange={(event: ChangeEvent<HTMLTextAreaElement>) => setDraft(event.target.value)} maxLength={2000} placeholder={canReply ? `Reply as ${adminName}…` : selected.status === 'waiting_for_admin' ? 'Claim the request before replying.' : selected.status === 'live' ? 'This chat is assigned to another administrator.' : 'Live support is not active.'} disabled={!canReply || busy} />
                <button className="btn primary" disabled={!canReply || busy || !draft.trim()}>Send reply</button>
              </form>
            </>
          )}
        </main>
      </div>
    </section>
  );
}
