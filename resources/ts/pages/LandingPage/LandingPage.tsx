import React from 'react'
import { Link } from 'react-router-dom'

import './LandingPage.scss'

export function LandingPage() {
    return (
        <div id="landing-page">
            <main>
                <div className="text-center hero">
                    <h1>Scrybble</h1>
                    <p className="lead">
                        <i>Your</i> reMarkable notes in <i>your</i> Obsidian vault
                    </p>
                    <a
                        href="https://streamsoft.gumroad.com/l/remarkable-to-obsidian"
                        className="btn btn-lg btn-secondary fw-bold border-white bg-white"
                    >
                        Learn more
                    </a>
                    <Link
                        to="/auth/register"
                        className="btn btn-lg btn-outline-secondary fw-bold"
                    >
                        I have a license
                    </Link>
                </div>
                {/*https://www.coolseotools.com/image-pencil-sketch*/}
                <div className="block remarkable-to-obsidian">
                    <div className="images">
                        <h2>
                            From
                            <img
                                className="remarkable"
                                src="/img/rm-sketch.jpg"
                                alt="ReMarkable logo"
                            />
                            to
                            <img
                                className="obsidian"
                                src="/img/obsidian-logo-sketch.png"
                                alt="obsidian-logo"
                            />
                        </h2>
                    </div>
                    <div className="copy">
                        <h2>Your reMarkable notes in your Obsidian vault</h2>
                        <p>
                            Wouldn't it be nice if you had access to everything on your
                            reMarkable tablet in your Obsidian vault?
                        </p>
                        <ul>
                            <li>The highlights in your textbooks and documents</li>
                            <li>Your annotations in the margins</li>
                            <li>
                                Your notebooks and quicksheets containing your ideas and
                                sketches
                            </li>
                        </ul>
                        <p className="lead">
                            <a
                                href="https://streamsoft.gumroad.com/l/remarkable-to-obsidian"
                                className="btn btn-lg btn-secondary fw-bold border-white bg-white mr-4"
                            >
                                I want my reMarkable notes in Obsidian!
                            </a>
                        </p>
                    </div>
                </div>
                <hr />
                <div className="block faq">
                    <div className="copy">
                        <h2>FAQ</h2>
                        <ul>
                            <li>Does this work with reMarkable version 3.0?</li>
                            <ul>
                                <li>Yes! Scrybble works with reMarkable version 3.0.</li>
                            </ul>
                            <li>Does Scrybble support the reMarkable Paper Pro?</li>
                            <ul>
                                <li>Partially, but we're still working on full support</li>
                            </ul>
                            <li>How does this work?</li>
                            <ul>
                                <li>
                                    You'll have to connect your reMarkable account to Scrybble,
                                    and install the Obsidian "Scrybble" plugin which will download
                                    the files you selected for synchronisation.
                                </li>
                            </ul>
                            <li>How long does it take to set-up?</li>
                            <ul>
                                <li>You'll be ready to go in less than five minutes</li>
                            </ul>
                            <li>Where can I learn more what scrybble does and doesn't do?</li>
                            <ul>
                                <li>
                                    Check out our <Link to="/roadmap">roadmap</Link>
                                </li>
                            </ul>
                        </ul>
                    </div>
                    <div className="images"></div>
                </div>
                <hr />
                <div className="block news">
                    <div className="copy">
                        <h2>In the news!</h2>

                        <div className="p-4 border-start border-4">
                            <div className="d-flex align-items-center gap-2 mb-4">
                                <span className="badge bg-secondary bg-opacity-25">Featured in</span>
                                <a href="https://www.bright.nl/nieuws/1231170/review-remarkable-paper-pro-is-een-perfect-apparaat-zonder-de-perfecte-flow.html"
                                   className="fw-semibold text-decoration-none"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    Bright.nl
                                </a>
                                <span className="fs-sm">(October 2024)</span>
                            </div>

                            <div className="ms-2">
                                <h3 className="fw-normal mb-4 lh-sm">
                                    "Review: reMarkable Paper Pro is a perfect device, without the perfect flow"
                                </h3>

                                <p>
                                    "the Paper Pro specifically targets productivity nerds [...]
                                    who value having a distraction-free device for note-taking, and who want to neatly
                                    organize their thoughts in the right folders on a computer."
                                </p>

                                <p>
                                    "And this group of people will quickly discover with Remarkable that this is a relatively closed ecosystem, requiring extra work on your part to sort everything in one place."
                                </p>

                                <p>
                                    "In the Obsidian note-taking app, someone has made a tool [scrybble] to automatically load all your writings into your note vault"
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    )
}
