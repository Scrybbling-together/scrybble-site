import React, { useCallback, useState } from 'react'
import './RMFileTree.scss'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import {
  useRMFileTreeQuery,
  useSelectFileForSyncMutation,
  useSyncStatusQuery,
} from '../../../store/api/apiRoot.ts'
import toast from 'react-hot-toast'
import { debounce } from 'lodash-es'
import { faHighlighter } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { Dialog } from '../../reusable/Dialog/Dialog.tsx'
import { Directory, File } from '../../../@types/ReMarkable.ts'
import { useGetUserQuery } from '../../../store/api/authApi.ts'

const syncSettingsEnabled = false

function DirectoryItem({ item }: { item: Directory }) {
  return (
    <span>
      <Link to={`/dashboard?path=${item.path}`}>{item.name}</Link>
    </span>
  )
}

function FileItem({ item }: { item: File }) {
  return <span>{item.name}</span>
}

function SyncSettings() {
  return (
    <div
      role="group"
      aria-label="Synchronization settings"
      className="btn-group btn-group-sm"
    >
      <button className="btn btn-primary">
        <FontAwesomeIcon icon={faHighlighter} />
      </button>
    </div>
  )
}

function useHasNoSyncs() {
  const { isSuccess: userLoaded } = useGetUserQuery()
  const { firstTime } = useSyncStatusQuery(undefined, {
    selectFromResult: ({ data, isSuccess }) => {
      return {
        firstTime: data ? data.length === 0 : false,
        isSuccess,
      }
    },
    skip: !userLoaded,
  })
  return firstTime
}

export default function RMFileTree() {
  const { search } = useLocation()
  const params = new URLSearchParams(search)
  const path = params.get('path')
  const {
    data: filetree,
    isLoading,
    isError,
    error,
  } = useRMFileTreeQuery(path ?? '/')
  const navigate = useNavigate()
  const firstTime = useHasNoSyncs()
  const [helpDialogClosed, setHelpDialogClosed] = useState(false)

  const [selectForSync, {}] = useSelectFileForSyncMutation()

  const syncFile = useCallback(
    debounce((item: File) => {
      toast.success(`File ${item.name} will be synced!`)
      selectForSync(item.path)
    }, 1000),
    [selectForSync]
  )

  if (error) {
    throw error
  }

  return (
    <div className="container">
      <Dialog
        open={firstTime && !helpDialogClosed}
        close={() => setHelpDialogClosed(true)}
        title="Since this is your first time"
        actions={
          <button
            onClick={() => setHelpDialogClosed(true)}
            className="btn btn-primary"
          >
            Close
          </button>
        }
      >
        <p>
          To get started, install the <a href="https://publish.obsidian.md/hub/02+-+Community+Expansions/02.05+All+Community+Expansions/Plugins/scrybble.ink">Scrybble sync</a> plugin in Obsidian.
        </p>
      </Dialog>
      {isLoading ? (
        'loading'
      ) : (
        <div id="filetree">
          <table className="table table-sm table-hover table-striped align-middle caption-top">
            <caption>{filetree.cwd}</caption>
            <colgroup>
              <col width="0*" />
              <col />
              <col width="0*" />
            </colgroup>
            <thead>
              <tr>
                <th></th>
                <th>File or directory</th>
                {syncSettingsEnabled ? <th>Sync settings</th> : <></>}
              </tr>
            </thead>
            <tbody>
              {filetree.items.map((item) => {
                if (item.type === 'd') {
                  return (
                    <tr
                      key={item.path}
                      className="directory"
                      role="link"
                      onClick={() => navigate(`/dashboard?path=${item.path}`)}
                    >
                      <td></td>
                      <td>
                        <DirectoryItem item={item as Directory} />
                      </td>
                      <td></td>
                    </tr>
                  )
                }
                return (
                  <tr key={item.path}>
                    <td
                      style={{
                        width: 'fit-content',
                        padding: '8px',
                      }}
                    >
                      <button
                        className="btn btn-success btn-sm"
                        onClick={() => {
                          syncFile(item as File)
                        }}
                      >
                        Sync now
                      </button>
                    </td>
                    <td className="file">
                      <FileItem item={item as File} />
                    </td>
                    {syncSettingsEnabled ? (
                      <td
                        style={{
                          textAlign: 'right',
                          padding: '0 8px',
                        }}
                      >
                        <SyncSettings />
                      </td>
                    ) : (
                      <></>
                    )}
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
