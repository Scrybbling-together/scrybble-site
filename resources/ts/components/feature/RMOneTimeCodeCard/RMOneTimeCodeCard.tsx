import React from 'react'
import {
  OnetimecodeQuery,
  useSendOnetimecodeMutation,
} from '../../../store/api/apiRoot.ts'
import FormError from '../../reusable/FormError/FormError.tsx'

export default function RMOneTimeCodeCard({
  firstTime,
}: {
  firstTime: boolean
}) {
  const [sendOnetimecode, { error, isLoading }] = useSendOnetimecodeMutation()

  let errMsg
  if (error && 'data' in error) {
    // @ts-ignore
    errMsg = error.data?.error
  }

  return (
    <div className="card">
      <div className="card-header">
        <span className="fs-4">
          Connect with reMarkable{' '}
          {firstTime ? (
            <span className="fs-5 text-muted">(step 2/2)</span>
          ) : null}
        </span>
      </div>
      <div className="card-body">
        {!firstTime ? (
          <div className="alert alert-warning">
            Your authentication token has expired, please log in with reMarkable
            again
          </div>
        ) : null}
        <p>
          Retrieve your{' '}
          <a
            target="_blank"
            href="https://my.remarkable.com/device/desktop/connect"
          >
            one-time-code
          </a>{' '}
          and fill it in below
        </p>
        {firstTime ? (
          <p>
            <strong>Note:</strong> connecting for the first time may take{' '}
            <em>well over a minute!</em>
          </p>
        ) : (
          <p>
            <strong>Note:</strong> connecting may take{' '}
            <em>well over a minute!</em>
          </p>
        )}
        <form
          onSubmit={(e) => {
            e.preventDefault()
            const oneTimeCodeBody = new FormData(
              e.currentTarget
            ) as unknown as OnetimecodeQuery
            sendOnetimecode(oneTimeCodeBody)
          }}
        >
          <div className="input-group">
            <input
              className={`input-group-text${errMsg ? ' is-invalid' : ''}`}
              required
              minLength={8}
              maxLength={8}
              pattern="[a-z]{8}"
              placeholder="aabbccdd"
              name="code"
              type="text"
              autoFocus
            />
            <button
              className="btn btn-primary"
              type="submit"
              disabled={isLoading}
            >
              {isLoading ? (
                <>
                  <div className="spinner-border spinner-border-sm" />
                  &nbsp;Checking code...
                </>
              ) : (
                `submit`
              )}
            </button>
            {errMsg ? <FormError errorMessage={errMsg} /> : null}
          </div>
        </form>
      </div>
    </div>
  )
}
