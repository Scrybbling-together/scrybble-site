import React, {
  DetailedHTMLProps,
  DialogHTMLAttributes,
  useEffect,
  useRef,
} from 'react'
import './dialog.scss'

export function Dialog({
  open,
  close,
  children,
  title,
  actions,
  ...props
}: {
  open: boolean
  close: () => void
  title: string
  children: JSX.Element
  actions: JSX.Element
} & Omit<
  DetailedHTMLProps<DialogHTMLAttributes<HTMLDialogElement>, HTMLDialogElement>,
  'ref' | 'onCancel'
>) {
  const ref = useRef<HTMLDialogElement | null>(null)
  let extendedClassNames = ''
  if (props['className']) {
    extendedClassNames = ' ' + props['className']
    delete props['className']
  }

  useEffect(() => {
    if (open) {
      ref.current?.showModal()
    } else {
      ref.current?.close()
    }
  }, [open])

  return (
    <dialog
      ref={ref}
      onCancel={close}
      className={extendedClassNames}
      {...props}
    >
      <div className="modal-content">
        <div className="modal-header border-bottom-0">
          <h2 className="modal-title">{title}</h2>
        </div>
        <div className="modal-body">{children}</div>
        <div className="modal-footer">{actions}</div>
      </div>
    </dialog>
  )
}
