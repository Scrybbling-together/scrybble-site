import { useParams } from 'react-router-dom'
import * as React from 'react'
import { useState } from 'react'
import { ErrorResponse } from '../../laravelTypes.ts'
import { ResetPasswordData } from '../../@types/Authentication.ts'
import { useResetPasswordMutation } from '../../store/api/authApi.ts'

export function ResetPasswordTokenCard() {
  const [resetPassword, { isSuccess, error, data }] = useResetPasswordMutation()
  const { token } = useParams()
  const urlParams = new URLSearchParams(window.location.search)
  const [email, setEmail] = useState(urlParams.get('email'))
  const [csrfFetched, setCsrfFetched] = useState(false)

  fetch('/sanctum/csrf-cookie').then(() => {
    setCsrfFetched(true)
  })

  // function hasAnyErrors(): boolean {
  //     let err = error as ErrorResponse
  //     return Boolean(err?.data.errors)
  // }
  //
  // function allErrors(): [] {
  //     let err = error as ErrorResponse
  //     return err?.data.errors ?? []
  // }

  function hasError(name: string): boolean {
    let err = error as ErrorResponse
    return err?.data.errors.hasOwnProperty(name) ?? false
  }

  function errMsg(name: string): string {
    let err = error as ErrorResponse
    return err?.data.errors[name][0] ?? ''
  }

  return (
    <div className="page-centering-container">
      <div className="card col-md-6">
        <div className="card-header">Choose a new password</div>
        <form
          className="card-body"
          onSubmit={(e) => {
            e.preventDefault()
            const formData = new FormData(
              e.currentTarget
            ) as unknown as ResetPasswordData
            resetPassword(formData)
          }}
        >
          <input type="hidden" name="token" value={token} />

          <div>
            <label htmlFor="email">Your email</label>
            <input
              value={email}
              onChange={(e) => {
                setEmail(e.currentTarget.value)
              }}
              id="email"
              className={`form-control${hasError('email') ? ' is-invalid' : ''}`}
              type="email"
              name="email"
              required
              autoFocus
            />
            {hasError('email') ? (
              <span className="invalid-feedback" role="alert">
                <strong>{errMsg('email')}</strong>
              </span>
            ) : null}
          </div>

          <div className="mt-4">
            <label htmlFor="password">Pick a new password</label>
            <input
              id="password"
              className={`form-control${hasError('password') ? ' is-invalid' : ''}`}
              type="password"
              name="password"
              required
            />
            {hasError('password') ? (
              <span className="invalid-feedback" role="alert">
                <strong>{errMsg('password')}</strong>
              </span>
            ) : null}
          </div>

          <div className="mt-4 mb-3">
            <label htmlFor="password_confirmation">Confirm your password</label>
            <input
              id="password_confirmation"
              className="form-control"
              type="password"
              name="password_confirmation"
              required
            />
          </div>

          <button
            type="submit"
            className="btn btn-primary"
            disabled={!csrfFetched}
          >
            Reset password
          </button>
          {isSuccess ? (
            <div>
              <strong className="text-success">{data.message}</strong>
            </div>
          ) : null}
        </form>
      </div>
    </div>
  )
}
