import * as React from 'react'

export function Roadmap() {
    return (
        <div className="container">
            <h1>Scrybble roadmap</h1>
            <table className="table">
                <thead>
                <tr>
                    <th>Supported</th>
                    <th>Feature</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr className="table-group-divider">
                    <td colSpan={3} className="table text-center">
                        <h4 style={{ marginBottom: 0 }}>Device support</h4>
                    </td>
                </tr>
                <tr className="table-success">
                    <td>Yes</td>
                    <td>reMarkable 1</td>
                    <td>Supported! As long as the reMarkable software is up-to-date</td>
                </tr>
                <tr className="table-success">
                    <td>Yes</td>
                    <td>reMarkable 2</td>
                    <td>Supported! As long as the reMarkable software is up-to-date!</td>
                </tr>
                <tr className="table-success">
                    <td>Yes</td>
                    <td>reMarkable Paper Pro</td>
                    <td>Supported!</td>
                </tr>
                <tr className="table-success">
                    <td>Yes</td>
                    <td>reMarkable Type Folio</td>
                    <td>Supported! Your typed text gets exported to Markdown, including
                        <pre><code>
                  #tags,
                  [[links]] and
                  - [ ] checkboxes
                  </code></pre>
                    </td>
                </tr>
                <tr className="table-group-divider">
                    <td colSpan={3} className="table text-center">
                        <h4 style={{ marginBottom: 0 }}>Filetype support</h4>
                    </td>
                </tr>
                <tr className="table-success">
                    <td>Yes</td>
                    <td>Support PDFs</td>
                    <td>
                        Allow synchronization of PDF files from reMarkable to Obsidian
                    </td>
                </tr>
                <tr className="table-success">
                    <td>Yes</td>
                    <td>Support .epub format</td>
                    <td>
                        Allow synchronization of .epub files from reMarkable to Obsidian
                    </td>
                </tr>
                <tr className="table-success">
                    <td>Yes</td>
                    <td>Support notebooks</td>
                    <td>Allows synchronizing reMarkable notebooks to Obsidian</td>
                </tr>
                <tr className="table-success">
                    <td>Yes</td>
                    <td>Quick sheets</td>
                    <td>Allow synchronizing quick sheets to Obsidian</td>
                </tr>
                <tr className="table-group-divider">
                    <td colSpan={3} className="table text-center">
                        <h4 style={{ marginBottom: 0 }}>Features</h4>
                    </td>
                </tr>
                <tr className="table-success">
                    <td>Yes</td>
                    <td>Tags</td>
                    <td>
                        Tags on both notebooks as well as on individual pages are exported to Markdown
                    </td>
                </tr>
                <tr className="table-group-divider">
                    <td colSpan={3} className="table text-center">
                        <h4 style={{ marginBottom: 0 }}>Website user interface</h4>
                    </td>
                </tr>
                <tr className="table-success">
                    <td>Yes (beta)</td>
                    <td>Show synchronization status</td>
                    <td>
                        There are multiple steps to synchronize a file from RM to
                        Obsidian, since mid-December 2022, we have a "Sync status" page to
                        inspect what does and doesn't sync as expected.
                    </td>
                </tr>
                <tr className="table-group-divider">
                    <td colSpan={3} className="table text-center">
                        <h4 style={{ marginBottom: 0 }}>Stability</h4>
                    </td>
                </tr>
                <tr className="table-info">
                    <td>-</td>
                    <td>Stability</td>
                    <td>
                        Scrybble has become more stable since it's initial release. Over
                        200 customers depend on scrybble!
                    </td>
                </tr>
                <tr className="table-group-divider">
                    <td colSpan={3} className="table text-center">
                        <h4 style={{ marginBottom: 0 }}>Synchronization</h4>
                    </td>
                </tr>
                <tr className="table-success">
                    <td>Yes</td>
                    <td>Export highlights to markdown</td>
                    <td>
                        This has been added in August 2023, any highlights in your
                        documents will appear in a markdown file next to your exported
                        PDF. The markdown file is populated with per-page tags, document
                        tags and per-page highlights. All tags are in the Obsidian format,
                        highlights are deep-linked as well.
                    </td>
                </tr>
                </tbody>
            </table>
            <h2>Contact us</h2>
            <p>Got feedback? Something missing?</p>
            <span>mail@scrybble.ink</span>
        </div>
    )
}
