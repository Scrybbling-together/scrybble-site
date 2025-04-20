import { useAppSelector } from '../../store/hooks.ts'
import { apiRoot } from '../../store/api/apiRoot.ts'
import { Link, Outlet, useNavigate } from 'react-router-dom'
import * as React from 'react'
import { useEffect } from 'react'
import './MainLayout.scss'
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { faBook, faEnvelope } from '@fortawesome/free-solid-svg-icons'
import { selectUser, useGetUserQuery, useLogoutMutation } from '../../store/api/authApi.ts'

const knowledgeBaseVisible = false
const obsidianPostsVisible = true

function Auth() {
    const { data, isSuccess, isError } = useGetUserQuery()
    const navigate = useNavigate()

    useEffect(() => {
        if (isSuccess) {
            if (data == null) {
                navigate('/auth/login')
            }
        }
        if (isError) {
            navigate('/auth/login')
        }
    }, [isSuccess])

    return null
}

export function MainLayout() {
    const user = useAppSelector(selectUser)
    const [logout, {}] = useLogoutMutation()

    const prefetchSyncStatus = apiRoot.usePrefetch('syncStatus')

    return (
        <>
            <Auth />
            <div id="mainLayout">
                <nav className="navbar navbar-expand-md shadow-sm mb-4">
                    <div className="container">
                        <Link className="navbar-brand" to="/">
                            Scrybble
                        </Link>
                        <button
                            className="navbar-toggler"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#navbarSupportedContent"
                            aria-controls="navbarSupportedContent"
                            aria-expanded="false"
                            aria-label="Toggle navigation"
                        >
                            <span className="navbar-toggler-icon" />
                        </button>

                        <div
                            className="collapse navbar-collapse"
                            id="navbarSupportedContent"
                        >
                            <ul className="navbar-nav me-auto">
                                <li className="nav-item">
                                    <Link to="/roadmap" className="nav-link">
                                        Roadmap
                                    </Link>
                                </li>
                                {knowledgeBaseVisible ? (
                                    <li className="nav-item">
                                        <Link to="/learn" className="nav-link text-info">
                                            <FontAwesomeIcon
                                                icon={faBook}
                                                style={{ marginRight: '4px' }}
                                            />
                                            Learn scrybble
                                        </Link>
                                    </li>
                                ) : null}
                                {obsidianPostsVisible ? (
                                    <li className="nav-item">
                                        <Link
                                            to="/learn/obsidian"
                                            className="nav-link text-obsidian"
                                        >
                                            <FontAwesomeIcon
                                                icon={faBook}
                                                style={{ marginRight: '4px' }}
                                            />
                                            Learn Obsidian
                                        </Link>
                                    </li>
                                ) : null}
                                <li className="mx-4"></li>
                                {user ? (
                                    <>
                                        <li className="nav-item">
                                            <Link to="/dashboard" className="nav-link">
                                                Dashboard
                                            </Link>
                                        </li>
                                        <li className="nav-item">
                                            <Link
                                                to="/inspect-sync"
                                                className="nav-link"
                                                onMouseEnter={() => {
                                                    prefetchSyncStatus(undefined)
                                                }}
                                            >
                                                Sync status
                                            </Link>
                                        </li>
                                    </>
                                ) : null}
                            </ul>

                            <ul className="navbar-nav ms-auto">
                                {!user ? (
                                    <>
                                        <li className="nav-item">
                                            <Link className="nav-link" to="/auth/login">
                                                Login
                                            </Link>
                                        </li>

                                        <li className="nav-item">
                                            <Link className="nav-link" to="/auth/register">
                                                Register
                                            </Link>
                                        </li>
                                    </>
                                ) : (
                                    <li className="nav-item dropdown">
                                        <Link
                                            id="navbarDropdown"
                                            className="nav-link dropdown-toggle"
                                            to="#"
                                            role="button"
                                            data-bs-toggle="dropdown"
                                            aria-haspopup="true"
                                            aria-expanded="false"
                                        >
                                            {user.name}
                                        </Link>

                                        <div
                                            className="dropdown-menu dropdown-menu-end"
                                            aria-labelledby="navbarDropdown"
                                        >
                                            <Link to="profile" className="dropdown-item">
                                                Profile
                                            </Link>
                                            <a
                                                className="dropdown-item"
                                                onClick={(e) => {
                                                    e.preventDefault()
                                                    logout()
                                                }}
                                            >
                                                Logout
                                            </a>
                                        </div>
                                    </li>
                                )}
                            </ul>
                        </div>
                    </div>
                </nav>
                <main
                    className="container d-flex flex-column"
                    style={{ flexGrow: 1, flexShrink: 0 }}
                >
                    <Outlet />
                </main>
                <footer className="border-top border-2">
                    <div className="row mb-4">
                        <div className="col">
                            <span>
                                <FontAwesomeIcon icon={faEnvelope} />&nbsp;
                                mail@scrybble.ink
                            </span>
                        </div>
                    </div>
                    <div className="row">
                      <span>
                      © {new Date().getFullYear()} Streamsoft. Streamsoft is a
                      sole-proprietorship registered in the Netherlands.
                      </span>
                    </div>
                </footer>
            </div>
        </>
    )
}
