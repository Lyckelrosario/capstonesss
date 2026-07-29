import { ChangeEvent, FormEvent, KeyboardEvent, useCallback, useEffect, useRef, useState } from 'react';
import { api, ApiError } from '../api';

type ConversationStatus = 'ai' | 'waiting_for_admin' | 'live' | 'closed';

type Message = {
  id: number;
  senderType: 'user' | 'ai' | 'admin' | 'system';
  body: string;
  createdAt: string | null;
};

type Conversation = {
  id: number;
  status: ConversationStatus;
  assignedAdmin: string | null;
  lastMessageAt: string | null;
};

type ChatResponse = {
  conversation: Conversation;
  messages: Message[];
};

type Props = {
  conversationId: number;
  currentUser: {
    id: number;
    name: string;
    avatarUrl: string | null;
  };
};

const statusCopy: Record<ConversationStatus, { label: string; description: string }> = {
  ai: { label: 'AI assistant', description: 'Instant automated support is active.' },
  waiting_for_admin: { label: 'Waiting for admin', description: 'Your request is visible to the support team.' },
  live: { label: 'Live support', description: 'An administrator is assisting you.' },
  closed: { label: 'Closed', description: 'This conversation has ended.' },
};

export default function ChatPage({ currentUser }: Props) {
  const [conversation, setConversation] = useState<Conversation | null>(null);
  const [messages, setMessages] = useState<Message[]>([]);
  const [draft, setDraft] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const chatEndRef = useRef<HTMLDivElement | null>(null);
  const pollingRef = useRef(false);

  const load = useCallback(async () => {
    if (pollingRef.current) return;
    pollingRef.current = true;
    try {
      const data = await api<ChatResponse>('/client/chat/messages');
      setConversation(data.conversation);
      setMessages(data.messages);
      setError('');
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Chat could not be loaded.');
    } finally {
      pollingRef.current = false;
    }
  }, []);

  useEffect(() => {
    void load();
    const timer = window.setInterval(() => void load(), 2500);
    return () => window.clearInterval(timer);
  }, [load]);

  useEffect(() => {
    chatEndRef.current?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }, [messages.length]);

  async function send(event: FormEvent) {
    event.preventDefault();
    const message = draft.trim();
    if (!message || busy) return;

    setBusy(true);
    setError('');
    try {
      await api('/client/chat/messages', {
        method: 'POST',
        body: JSON.stringify({ message }),
      });
      setDraft('');
      await load();
    } catch (requestError) {
      setError(requestError instanceof ApiError ? requestError.message : 'Message could not be sent.');
    } finally {
      setBusy(false);
    }
  }

  async function requestLive() {
    setBusy(true);
    setError('');
    try {
      const data = await api<{ conversation: Conversation }>('/client/chat/request-live', { method: 'POST' });
      setConversation(data.conversation);
      await load();
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Live support could not be requested.');
    } finally {
      setBusy(false);
    }
  }

  async function cancelLiveRequest() {
    setBusy(true);
    try {
      const data = await api<{ conversation: Conversation }>('/client/chat/return-to-ai', { method: 'POST' });
      setConversation(data.conversation);
      await load();
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'The live request could not be cancelled.');
    } finally {
      setBusy(false);
    }
  }

  const activeStatus = conversation ? statusCopy[conversation.status] : statusCopy.ai;
  const canSend = conversation?.status !== 'closed';

  return (
    <section className="support-shell" aria-labelledby="support-title">
      <header className="support-header">
        <div>
          <p className="eyebrow">Customer support</p>
          <h1 id="support-title">AI and live assistance</h1>
          <p className="support-subtitle">Your conversation is saved so an administrator can review the AI responses and join when you request live support.</p>
        </div>
        <div className={`support-status status-${conversation?.status ?? 'ai'}`}>
          <span className="status-dot" aria-hidden="true" />
          <div><strong>{activeStatus.label}</strong><small>{activeStatus.description}</small></div>
        </div>
      </header>

      <div className="support-card">
        <div className="conversation-toolbar">
          <div className="identity-row">
            <div className="avatar avatar-small">{currentUser.avatarUrl ? <img src={currentUser.avatarUrl} alt="" /> : currentUser.name.slice(0, 1).toUpperCase()}</div>
            <div><strong>{currentUser.name}</strong><small>Conversation #{conversation?.id ?? '—'}</small></div>
          </div>
          <div className="toolbar-actions">
            {conversation?.status === 'ai' && <button className="btn secondary" type="button" onClick={requestLive} disabled={busy}>Request live admin</button>}
            {conversation?.status === 'waiting_for_admin' && <button className="btn ghost" type="button" onClick={cancelLiveRequest} disabled={busy}>Cancel request</button>}
            {conversation?.status === 'live' && <span className="assigned-admin">Assisted by {conversation.assignedAdmin ?? 'an administrator'}</span>}
          </div>
        </div>

        <div className="message-list" role="log" aria-live="polite" aria-label="Support conversation">
          {messages.length === 0 && (
            <div className="empty-chat">
              <div className="empty-chat-icon">4BS</div>
              <h2>How can we help?</h2>
              <p>Ask about appointments, available products, or describe a minor vehicle concern.</p>
            </div>
          )}
          {messages.map((message) => (
            <article key={message.id} className={`message message-${message.senderType}`}>
              <div className="message-meta">
                <strong>{message.senderType === 'user' ? 'You' : message.senderType === 'ai' ? '4BS AI' : message.senderType === 'admin' ? '4BS Support' : 'System'}</strong>
                {message.createdAt && <time>{new Date(message.createdAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</time>}
              </div>
              <p>{message.body}</p>
            </article>
          ))}
          {busy && draft === '' && <div className="typing-indicator" aria-label="Processing"><span /><span /><span /></div>}
          <div ref={chatEndRef} />
        </div>

        {error && <div className="inline-error" role="alert">{error}</div>}

        <form className="message-composer" onSubmit={send}>
          <textarea
            value={draft}
            onChange={(event: ChangeEvent<HTMLTextAreaElement>) => setDraft(event.target.value)}
            onKeyDown={(event: KeyboardEvent<HTMLTextAreaElement>) => {
              if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                event.currentTarget.form?.requestSubmit();
              }
            }}
            placeholder={canSend ? 'Type your message…' : 'This conversation is closed.'}
            maxLength={2000}
            disabled={!canSend || busy}
            aria-label="Message"
          />
          <button className="btn primary composer-send" disabled={!canSend || busy || draft.trim() === ''}>Send</button>
        </form>
        <p className="chat-disclaimer">AI guidance is informational and does not replace a physical safety inspection.</p>
      </div>
    </section>
  );
}
