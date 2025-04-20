import React, { useState } from 'react'
import './GumroadLicenseCard.scss'
import { useSendGumroadLicenseMutation } from '../../../store/api/apiRoot.ts'
import FormError from '../../reusable/FormError/FormError.tsx'

export default function GumroadLicenseCard() {
  const [sendGumroadLicense, { isSuccess, error }] =
    useSendGumroadLicenseMutation()
  const [license, setLicense] = useState('')

  let errMsg
  if (error && 'data' in error) {
    // @ts-ignore
    errMsg = error.data?.error
  }

  return (
    <div id="login-card" className="card">
      <div className="card-header">
        <h1 className="fs-4">
          Connect your gumroad license
          <span className="fs-5 text-muted"> (step 1/2)</span>
        </h1>
      </div>
      <form
        className="card-body"
        onSubmit={(e) => {
          e.preventDefault()
          sendGumroadLicense(license)
        }}
      >
        <div className="input-group">
          <input
            type="text"
            className={`form-control input-group-text${errMsg ? ' is-invalid' : ''}`}
            required
            name="license"
            placeholder="Your license"
            value={license}
            onChange={(e) => setLicense(e.currentTarget.value)}
          />
          <button className="btn btn-primary" type="submit">
            Submit
          </button>
          {errMsg ? <FormError errorMessage={errMsg} /> : null}
        </div>
        <hr />
        <div className="no-license">
          <a
            href="https://streamsoft.gumroad.com/l/remarkable-to-obsidian"
            className="fs-5 link-info"
            target="_blank"
          >
            Don't have a license? Try scrybble for free
          </a>
          <p>The first month is on us. You can cancel at any time.</p>
        </div>
      </form>
    </div>
  )
}
