# Landing Page Scanner

Landing Page Scanner is a modern, responsive web application that audits any landing page for SEO signals and Lighthouse performance metrics. Enter a URL to instantly discover metadata issues, Core Web Vitals opportunities, and key optimization insights. The experience is tuned for marketers and growth teams who need fast, visual reports they can share.

## Features

- 🔍 **SEO analysis** – Extracts title length, meta description, headings, image alt coverage, canonical tag, and crawl directives.
- ⚡ **Performance insights** – Runs Lighthouse to surface Speed Index, LCP, Max Potential FID, TBT, image optimization opportunities, and resource size breakdowns.
- 🌓 **Light/dark theme** – Toggle-friendly UI that works beautifully on desktop and mobile.
- 🕒 **Scan history** – Automatically saves recent scans in `localStorage` for quick recall.
- 📄 **Shareable summaries** – Color-coded badges and responsive cards highlight what’s working and what needs attention.

## Tech stack

- **Frontend:** React 18, Vite, Tailwind CSS, React Icons
- **Backend:** Node.js (Express), Lighthouse, chrome-launcher, node-fetch, Cheerio

## Getting started

> **Prerequisite:** Node.js 18+ (Lighthouse requires a modern Node runtime and Chrome installation).

1. **Install dependencies**

   ```bash
   npm install --prefix server
   npm install --prefix client
   ```

   Installing Lighthouse will download a compatible Chromium build on first run.

2. **Run the development servers**

   ```bash
   # terminal 1 – API
   npm run dev --prefix server

   # terminal 2 – Frontend
   npm run dev --prefix client
   ```

   The Vite dev server proxies `/api/*` requests to `http://localhost:5000` for a seamless local workflow.

3. **Build for production**

   ```bash
   npm run build --prefix client
   ```

   Deploy the static assets in `client/dist` alongside the Express server (`server/src/index.js`).

## Environment variables

No environment variables are required by default. If you need to tweak Lighthouse behaviour (e.g., throttling), adjust the options in `server/src/utils/performanceAnalyzer.js`.

## Project structure

```
client/   # React + Tailwind UI
server/   # Express API, SEO + performance analyzers
```

## Testing the scanner

Run the dev servers, open `http://localhost:5173`, and test with URLs such as:

- https://www.apple.com
- https://web.dev
- https://www.notion.so

Large sites may take 30–60 seconds to complete a Lighthouse run. For the best experience, keep Chrome updated locally.

## Troubleshooting

- **Lighthouse fails to launch Chrome:** ensure you have the necessary system libraries for Chromium and try setting `CHROME_PATH` to a local Chrome binary.
- **Robots.txt or sitemap checks fail:** some sites block `HEAD` requests. The analyzer automatically retries with `GET`, but corporate firewalls can still prevent access.
- **Network-restricted environments:** when npm registry access is blocked, configure the `registry` setting (e.g., `npm config set registry https://registry.npmjs.org/`) before installing dependencies.

Happy auditing!
