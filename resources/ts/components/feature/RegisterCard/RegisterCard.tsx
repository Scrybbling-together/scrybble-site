import { useGumroadSaleInfoQuery } from '../../../store/api/apiRoot.ts'
import * as React from 'react'
import { useEffect, useState } from 'react'
import { ErrorResponse } from '../../../laravelTypes.ts'
import { useSearchParams } from 'react-router-dom'
import { RegisterForm } from '../../../@types/Authentication.ts'
import { useLogin, useRegisterMutation } from '../../../store/api/authApi.ts'

function usePrefillEmailField() {
  const [emailField, setEmailField] = useState<HTMLInputElement | null>(null)
  const [params] = useSearchParams()
  const sale_id: string | undefined = params.get('sale_id')
  const { data: userPrefill, isSuccess: prefillSuccess } =
    useGumroadSaleInfoQuery(sale_id, { skip: sale_id === undefined })

  useEffect(() => {
    if (emailField && prefillSuccess && userPrefill) {
      emailField.value = userPrefill.email
    }
  }, [userPrefill, prefillSuccess, emailField])

  return setEmailField
}

export function RegisterCard() {
  const [register, { error, isSuccess }] = useRegisterMutation()
  const login = useLogin('/dashboard')

  const emailRef = usePrefillEmailField()

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
      login()
    }
  }, [isSuccess])

  return (
    <div className="page-centering-container">
      <div className="col-md-8">
        <div className="card">
          <div className="card-header">Register</div>

          <div className="card-body">
            <form
              onSubmit={(e) => {
                e.preventDefault()
                const registration = new FormData(
                  e.currentTarget
                ) as unknown as RegisterForm

                register(registration)
              }}
            >
              <div className="row mb-3">
                <label
                  htmlFor="name"
                  className="col-md-4 col-form-label text-md-end"
                >
                  Name
                </label>

                <div className="col-md-6">
                  <input
                    id="name"
                    type="text"
                    className={`form-control${hasError('name') ? ' is-invalid' : ''}`}
                    name="name"
                    required
                    autoComplete="name"
                    autoFocus
                  />

                  {hasError('name') ? (
                    <span className="invalid-feedback" role="alert">
                      <strong>{errMsg('name')}</strong>
                    </span>
                  ) : null}
                </div>
              </div>

              <div className="row mb-3">
                <label
                  htmlFor="email"
                  className="col-md-4 col-form-label text-md-end"
                >
                  Email
                </label>

                <div className="col-md-6">
                  <input
                    id="email"
                    type="email"
                    ref={emailRef}
                    className={`form-control${hasError('email') ? ' is-invalid' : ''}`}
                    name="email"
                    required
                    autoComplete="email"
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
                    autoComplete="new-password"
                  />

                  {hasError('password') ? (
                    <span className="invalid-feedback" role="alert">
                      <strong>{errMsg('password')}</strong>
                    </span>
                  ) : null}
                </div>
              </div>

              <div className="row mb-3">
                <label
                  htmlFor="password-confirm"
                  className="col-md-4 col-form-label text-md-end"
                >
                  Confirm password
                </label>

                <div className="col-md-6">
                  <input
                    id="password-confirm"
                    type="password"
                    className="form-control"
                    name="password_confirmation"
                    required
                    autoComplete="new-password"
                  />
                </div>
              </div>

              <div className="row mb-0">
                <div className="col-md-6 offset-md-4">
                  <button type="submit" className="btn btn-primary">
                    Register
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  )
}
