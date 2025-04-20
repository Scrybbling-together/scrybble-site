import React, { useEffect } from 'react'
import { ErrorResponse } from '../../../laravelTypes.ts'
import { Link } from 'react-router-dom'
import { LoginData } from '../../../@types/Authentication.ts'
import { useLogin, useLoginMutation } from '../../../store/api/authApi.ts'

export default function () {
  const [login, { isSuccess, error }] = useLoginMutation()
  const authenticateUser = useLogin('/dashboard')

  function hasError(name: string): boolean {
    let err = error as ErrorResponse
    return err?.data.errors.hasOwnProperty(name) ?? false
  }

  function errMsg(name: string): string {
    let err = error as ErrorResponse
    return err?.data.errors[name][0] ?? ''
  }

  useEffect(() => {
    if (isSuccess) {
      authenticateUser()
    }
  }, [isSuccess])

  return (
    <div className="card">
      <div className="card-header">Login</div>
      <form
        className="card-body"
        onSubmit={(e) => {
          e.preventDefault()
          const formData = new FormData(e.currentTarget) as unknown as LoginData
          login(formData)
        }}
      >
        <div className="row mb-3">
          <label
            htmlFor="email"
            className="col-md-4 col-form-label text-md-end"
          >
            Email address
          </label>

          <div className="col-md-6">
            <input
              id="email"
              type="email"
              className={`form-control${hasError('email') ? ' is-invalid' : ''}`}
              name="email"
              required
              autoComplete="email"
              autoFocus
            />

            {hasError('email') ? (
              <span className="invalid-feedback" role="alert">
                <strong>{errMsg('email')}</strong>
              </span>
            ) : null}
          </div>
        </div>

        <div className="row mb-3">
          <label
            htmlFor="password"
            className="col-md-4 col-form-label text-md-end"
          >
            Password
          </label>

          <div className="col-md-6">
            <input
              id="password"
              type="password"
              className={`form-control${hasError('password') ? ' is-invalid' : ''}`}
              name="password"
              required
              autoComplete="current-password"
            />
            {hasError('password') ? (
              <span className="invalid-feedback" role="alert">
                <strong>{errMsg('password')}</strong>
              </span>
            ) : null}
          </div>
        </div>

        <div className="row mb-3">
          <div className="col-md-6 offset-md-4">
            <div className="form-check">
              <input
                className="form-check-input"
                type="checkbox"
                name="remember"
                id="remember"
              />

              <label className="form-check-label" htmlFor="remember">
                Remember me
              </label>
            </div>
          </div>
        </div>

        <div className="row mb-0">
          <div className="col-md-8 offset-md-4">
            <button type="submit" className="btn btn-primary">
              Login
            </button>
          </div>
        </div>

        <div className="row mb-3">
          <div className="col-md-6 offset-md-4">
            <Link to="/auth/reset-password">Forgot your password?</Link>
          </div>
        </div>
      </form>
    </div>
  )
}
