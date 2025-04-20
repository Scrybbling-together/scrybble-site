import { ErrorResponse } from '../../../laravelTypes.ts'
import * as React from 'react'
import { RequestPasswordResetData } from '../../../@types/Authentication.ts'
import { useRequestPasswordResetMutation } from '../../../store/api/authApi.ts'

export default function ResetPasswordCard() {
  const [resetPasswordRequest, { isSuccess, error, data }] =
    useRequestPasswordResetMutation()

  function hasError(name: string): boolean {
    let err = error as ErrorResponse
    return err?.data.errors.hasOwnProperty(name) ?? false
  }

  function errMsg(name: string): string {
    let err = error as ErrorResponse
    return err?.data.errors[name][0] ?? ''
  }

  return (
    <>
      <div className="card col-md-6">
        <div className="card-header">Reset password</div>
        <form
          className="card-body"
          onSubmit={(e) => {
            e.preventDefault()
            const formData = new FormData(
              e.currentTarget
            ) as unknown as RequestPasswordResetData
            resetPasswordRequest(formData)
          }}
        >
          <div className="mb-4 text-sm text-gray-600">
            Forgot your password? No problem. Just let us know your email
            address and we will email you a password reset link that will allow
            you to choose a new one.
          </div>
          <div className="input-group">
            <input
              id="email"
              className={`form-control${hasError('email') ? ' is-invalid' : ''}`}
              type="email"
              placeholder="email@example.com"
              name="email"
              required
              autoFocus
            />
            <button className="btn btn-primary">
              Email Password Reset Link
            </button>
            {hasError('email') ? (
              <span className="invalid-feedback" role="alert">
                <strong>{errMsg('email')}</strong>
              </span>
            ) : null}
          </div>
          {isSuccess ? (
            <div className="text-success" role="alert">
              <strong>{data['message']}</strong>
            </div>
          ) : null}
        </form>
      </div>
    </>
  )
}
