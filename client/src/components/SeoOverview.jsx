import {
  FiAlertTriangle,
  FiCheckCircle,
  FiFileText,
  FiHash,
  FiImage,
  FiLink,
  FiShield,
} from 'react-icons/fi';
import { StatusBadge } from './StatusBadge.jsx';

const SectionCard = ({ icon: Icon, title, status, children }) => (
  <section className="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/80 p-5 shadow-md transition hover:-translate-y-0.5 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900/60">
    <header className="flex items-center justify-between">
      <div className="flex items-center gap-3 text-sm font-semibold text-slate-600 dark:text-slate-200">
        <Icon className="text-lg text-primary-500" />
        {title}
      </div>
      {status ? <StatusBadge status={status} /> : null}
    </header>
    <div className="text-sm text-slate-600 dark:text-slate-300">{children}</div>
  </section>
);

const renderList = (items) =>
  items.length ? (
    <ul className="flex flex-col gap-2">
      {items.map((text, index) => (
        <li key={`${text}-${index}`} className="rounded-xl bg-slate-100/80 px-4 py-2 text-sm font-medium text-slate-700 dark:bg-slate-800/80 dark:text-slate-200">
          {text}
        </li>
      ))}
    </ul>
  ) : (
    <p className="flex items-center gap-2 text-sm font-medium text-slate-500">
      <FiAlertTriangle className="text-base text-amber-500" /> No items detected.
    </p>
  );

export const SeoOverview = ({ data }) => {
  if (!data) return null;

  const { title, metaDescription, headings, imageAlt, canonical, robots, sitemap } = data;

  return (
    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <SectionCard icon={FiFileText} title="Title Tag" status={title.status}>
        <p className="text-base font-semibold text-slate-800 dark:text-slate-100">{title.value || 'Missing title tag'}</p>
        <p className="mt-2 text-xs uppercase tracking-wide text-slate-400">Length: {title.length} characters</p>
      </SectionCard>

      <SectionCard icon={FiFileText} title="Meta Description" status={metaDescription.status}>
        <p className="text-base font-semibold text-slate-800 dark:text-slate-100">
          {metaDescription.value || 'Meta description not found'}
        </p>
        <p className="mt-2 text-xs uppercase tracking-wide text-slate-400">Length: {metaDescription.length} characters</p>
      </SectionCard>

      <SectionCard icon={FiHash} title="H1 Tags" status={headings.status}>
        {renderList(headings.h1)}
      </SectionCard>

      <SectionCard icon={FiHash} title="H2 Tags" status={headings.h2.length ? 'good' : 'info'}>
        {renderList(headings.h2)}
      </SectionCard>

      <SectionCard icon={FiImage} title="Image Alt Attributes" status={imageAlt.status}>
        <p className="text-sm">{imageAlt.total} images detected</p>
        {imageAlt.missing === 0 ? (
          <p className="mt-2 inline-flex items-center gap-2 text-sm font-medium text-emerald-600 dark:text-emerald-300">
            <FiCheckCircle /> All images have descriptive alt text.
          </p>
        ) : (
          <p className="mt-2 inline-flex items-center gap-2 text-sm font-medium text-amber-600 dark:text-amber-300">
            <FiAlertTriangle /> {imageAlt.missing} images missing alt attributes.
          </p>
        )}
      </SectionCard>

      <SectionCard icon={FiLink} title="Canonical Tag" status={canonical.status}>
        {canonical.value ? (
          <a href={canonical.value} target="_blank" rel="noreferrer" className="break-all text-sm font-semibold">
            {canonical.value}
          </a>
        ) : (
          <p className="text-sm font-medium text-amber-600 dark:text-amber-300">No canonical tag found.</p>
        )}
      </SectionCard>

      <SectionCard icon={FiShield} title="Robots.txt" status={robots.status}>
        {robots.present ? (
          <p className="inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 dark:text-emerald-300">
            <FiCheckCircle /> Accessible at
            <a className="truncate text-primary-600 dark:text-primary-300" href={robots.url} target="_blank" rel="noreferrer">
              {robots.url}
            </a>
          </p>
        ) : (
          <p className="inline-flex items-center gap-2 text-sm font-medium text-amber-600 dark:text-amber-300">
            <FiAlertTriangle /> robots.txt is missing.
          </p>
        )}
      </SectionCard>

      <SectionCard icon={FiShield} title="Sitemap" status={sitemap.status}>
        {sitemap.present ? (
          <p className="inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 dark:text-emerald-300">
            <FiCheckCircle /> Found at
            <a className="truncate text-primary-600 dark:text-primary-300" href={sitemap.url} target="_blank" rel="noreferrer">
              {sitemap.url}
            </a>
          </p>
        ) : (
          <p className="inline-flex items-center gap-2 text-sm font-medium text-amber-600 dark:text-amber-300">
            <FiAlertTriangle /> Sitemap could not be located.
          </p>
        )}
      </SectionCard>
    </div>
  );
};
