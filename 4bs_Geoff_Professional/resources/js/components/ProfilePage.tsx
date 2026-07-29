import { ChangeEvent, FormEvent, useEffect, useRef, useState } from 'react';
import { api } from '../api';

type User = { name: string; email: string; phone: string | null; avatarUrl: string | null };
type Props = { user: User };

export default function ProfilePage({ user: initialUser }: Props) {
  const [user, setUser] = useState(initialUser);
  const [name, setName] = useState(initialUser.name);
  const [phone, setPhone] = useState(initialUser.phone ?? '');
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const [cameraActive, setCameraActive] = useState(false);
  const videoRef = useRef<HTMLVideoElement | null>(null);
  const streamRef = useRef<MediaStream | null>(null);

  useEffect(() => () => {
    streamRef.current?.getTracks().forEach((track) => track.stop());
    streamRef.current = null;
    if (videoRef.current) videoRef.current.srcObject = null;
  }, []);

  async function saveProfile(event: FormEvent) {
    event.preventDefault();
    setBusy(true);
    setError('');
    try {
      const data = await api<{ message: string; user: User }>('/client/profile', {
        method: 'PUT',
        body: JSON.stringify({ name, phone }),
      });
      setUser(data.user);
      setMessage(data.message);
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Profile could not be updated.');
    } finally {
      setBusy(false);
    }
  }

  async function upload(file: File) {
    const formData = new FormData();
    formData.append('avatar', file);
    setBusy(true);
    setError('');
    try {
      const data = await api<{ message: string; avatarUrl: string }>('/client/profile/avatar', { method: 'POST', body: formData });
      setUser((current) => ({ ...current, avatarUrl: data.avatarUrl }));
      setMessage(data.message);
      stopCamera();
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Profile photo could not be uploaded.');
    } finally {
      setBusy(false);
    }
  }

  async function startCamera() {
    setError('');
    if (!navigator.mediaDevices?.getUserMedia) {
      setError('Camera access is not supported by this browser. Use Upload photo instead.');
      return;
    }
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
      streamRef.current = stream;
      setCameraActive(true);
      window.setTimeout(() => {
        if (videoRef.current) videoRef.current.srcObject = stream;
      }, 0);
    } catch {
      setError('Camera permission was denied or no camera is available.');
    }
  }

  function stopCamera() {
    streamRef.current?.getTracks().forEach((track) => track.stop());
    streamRef.current = null;
    if (videoRef.current) videoRef.current.srcObject = null;
    setCameraActive(false);
  }

  async function capturePhoto() {
    const video = videoRef.current;
    if (!video || video.videoWidth === 0 || video.videoHeight === 0) {
      setError('The camera is still starting. Please try again.');
      return;
    }

    const size = Math.min(video.videoWidth, video.videoHeight);
    const canvas = document.createElement('canvas');
    canvas.width = 640;
    canvas.height = 640;
    const context = canvas.getContext('2d');
    if (!context) return;
    context.drawImage(video, (video.videoWidth - size) / 2, (video.videoHeight - size) / 2, size, size, 0, 0, 640, 640);

    canvas.toBlob((blob) => {
      if (blob) void upload(new File([blob], `profile-${Date.now()}.jpg`, { type: 'image/jpeg' }));
    }, 'image/jpeg', 0.9);
  }

  return (
    <section className="profile-shell" aria-labelledby="profile-title">
      <header><p className="eyebrow">Account settings</p><h1 id="profile-title">Your profile</h1><p>Keep your contact details and profile photo current for faster support and appointments.</p></header>
      <div className="profile-grid">
        <article className="profile-card photo-card">
          <div className="avatar avatar-xl">{user.avatarUrl ? <img src={user.avatarUrl} alt={`${user.name} profile`} /> : user.name.slice(0, 1).toUpperCase()}</div>
          <h2>{user.name}</h2><p>{user.email}</p>
          <div className="photo-actions">
            <label className="btn primary file-button">Upload photo<input type="file" accept="image/jpeg,image/png,image/webp" onChange={(event: ChangeEvent<HTMLInputElement>) => { const file = event.target.files?.[0]; if (file) void upload(file); event.currentTarget.value = ''; }} disabled={busy} /></label>
            <button className="btn secondary" type="button" onClick={startCamera} disabled={busy || cameraActive}>Take a photo</button>
          </div>
          <small>JPG, PNG, or WebP. Maximum 5 MB.</small>
        </article>

        <article className="profile-card details-card">
          <h2>Personal details</h2>
          <form onSubmit={saveProfile}>
            <label htmlFor="profile-name">Full name</label><input id="profile-name" value={name} onChange={(event: ChangeEvent<HTMLInputElement>) => setName(event.target.value)} maxLength={120} required />
            <label htmlFor="profile-email">Email</label><input id="profile-email" value={user.email} disabled /><small className="field-help">Contact support to change a verified email address.</small>
            <label htmlFor="profile-phone">Phone number</label><input id="profile-phone" value={phone} onChange={(event: ChangeEvent<HTMLInputElement>) => setPhone(event.target.value)} maxLength={30} />
            <button className="btn primary" disabled={busy}>Save changes</button>
          </form>
          {message && <div className="inline-success" role="status">{message}</div>}
          {error && <div className="inline-error" role="alert">{error}</div>}
        </article>
      </div>

      {cameraActive && (
        <div className="camera-modal" role="dialog" aria-modal="true" aria-labelledby="camera-title">
          <div className="camera-dialog">
            <div className="camera-header"><div><p className="eyebrow">Profile photo</p><h2 id="camera-title">Take a photo</h2></div><button className="icon-button" type="button" onClick={stopCamera} aria-label="Close camera">×</button></div>
            <video ref={videoRef} autoPlay playsInline muted />
            <div className="camera-actions"><button className="btn ghost" type="button" onClick={stopCamera}>Cancel</button><button className="btn primary" type="button" onClick={capturePhoto} disabled={busy}>Use this photo</button></div>
          </div>
        </div>
      )}
    </section>
  );
}
