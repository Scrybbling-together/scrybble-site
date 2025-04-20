import * as React from 'react'
import { Suspense } from 'react'

import { createRoot } from 'react-dom/client'
import { Provider } from 'react-redux'
import { store } from './store/store.ts'
import {
  createBrowserRouter,
  createRoutesFromChildren,
  Link,
  matchRoutes,
  Route,
  RouterProvider,
  useLocation,
  useNavigationType,
  useParams,
  useRouteError,
} from 'react-router-dom'
import LoginCard from './components/feature/LoginCard/LoginCard.tsx'
import { usePostQuery, usePostsQuery } from './store/api/apiRoot.ts'
import { AuthPage } from './layout/AuthLayout.tsx'
import { MainLayout } from './layout/MainLayout/MainLayout.tsx'
import { Roadmap } from './pages/Roadmap.tsx'
import { RegisterCard } from './components/feature/RegisterCard/RegisterCard.tsx'
import { LandingPage } from './pages/LandingPage/LandingPage.tsx'
import { Toaster } from 'react-hot-toast'
import ResetPasswordCard from './components/feature/ResetPasswordCard/ResetPasswordCard.tsx'
import * as Sentry from '@sentry/react'
import { ResetPasswordTokenCard } from './components/feature/ResetPasswordTokenCard.tsx'
import UserProfile from './pages/UserProfile/UserProfile.tsx'
import ReactMarkdown from 'react-markdown'

let Dashboard = React.lazy(() => import('./pages/Dashboard.tsx'))
let InspectSync = React.lazy(
  () => import('./pages/InspectSync/InspectSync.tsx')
)
let PurchasedPage = React.lazy(() => import('./pages/PurchasedPage.tsx'))

Sentry.init({
  dsn: 'https://4201915825194ef6ab9263518b836ee4@o199243.ingest.sentry.io/4504527483305984',
  integrations: [
    Sentry.reactRouterV6BrowserTracingIntegration({
      useEffect: React.useEffect,
      useLocation,
      useNavigationType,
      createRoutesFromChildren,
      matchRoutes,
    }),
  ],
  tunnel: '/tunnel',

  // Set tracesSampleRate to 1.0 to capture 100%
  // of transactions for performance monitoring.
  // We recommend adjusting this value in production
  tracesSampleRate: 1.0,
})

const sentryCreateBrowserRouter =
  Sentry.wrapCreateBrowserRouter(createBrowserRouter)

function ObsidianPosts() {
  const { data, isLoading } = usePostsQuery()

  return (
    <div style={{ width: '640px' }}>
      <h1>Learning Obsidian</h1>
      <p>
        Obsidian is an incredibly powerful and open-ended tool for personal
        knowledge management. Learning to use Obsidian for your goals isn't a
        straight-forward task.
      </p>
      <p>
        Our guides are meant to be <b>practical</b> and easy to follow for any
        audience with a focus on optimizing your Obsidian vault for your goals.
      </p>

      <h2>Guides</h2>
      {isLoading
        ? 'Loading...'
        : data.map((post) => (
            <Link to={`/learn/obsidian/${post.slug}`}>{post.title}</Link>
          ))}
    </div>
  )
}

function ObsidianPost() {
  const { slug } = useParams()
  const { data: post, isLoading } = usePostQuery(slug)

  return (
    <>
      {isLoading ? (
        'Loading...'
      ) : (
        <div style={{ width: '640px' }}>
          <h1>{post.title}</h1>
          <ReactMarkdown>{post.content}</ReactMarkdown>
        </div>
      )}
    </>
  )
}

function DashboardError() {
  const error = useRouteError()
  console.log(`HIIII there was an error: ${error}`)
  return 'There was an error loading your reMarkable files.'
}

const routes = (
  <Route path="/" element={<MainLayout />}>
    <Route path="purchased" element={<PurchasedPage />} />
    <Route
      path="dashboard"
      element={<Dashboard />}
      errorElement={<DashboardError />}
    />
    <Route path="inspect-sync" element={<InspectSync />} />

    <Route path="profile" element={<UserProfile />}></Route>
    <Route path="auth" element={<AuthPage />}>
      <Route path="login" element={<LoginCard />} />
      <Route path="register" element={<RegisterCard />} />
      <Route path="reset-password" element={<ResetPasswordCard />} />
    </Route>
    <Route path="base" element={<AuthPage />}>
      <Route
        path="reset-password/:token"
        element={<ResetPasswordTokenCard />}
      />
    </Route>
    <Route path="learn">
      <Route path="obsidian" element={<ObsidianPosts />} />
      <Route path="obsidian/:slug" element={<ObsidianPost />} />
    </Route>
    <Route path="roadmap" element={<Roadmap />} />
    <Route index element={<LandingPage />} />
  </Route>
)

const router = sentryCreateBrowserRouter(createRoutesFromChildren(routes))

function App() {
  return (
    <Provider store={store}>
      <Suspense>
        <RouterProvider router={router} />
        <Toaster />
      </Suspense>
    </Provider>
  )
}

const root = document.querySelector('#root')
if (root) {
  createRoot(root).render(<App />)
}
