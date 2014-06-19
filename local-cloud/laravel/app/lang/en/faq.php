<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | FAQ Sections Language Lines
    |--------------------------------------------------------------------------
    |
    | The following lines are section names for the FAQ page. The keys serve as
    | html ids and values will be displayed as section headers.
    |
    */

    'sections' => array(
        'account-info'           => 'Account Information',
        'billing'                => 'Billing',
        'collaborators'          => 'Collaborators',
        'tailwind-terms-defined' => 'Tailwind Terms Defined',
        'competitors'            => 'Competitors',
        'google-analytics'       => 'Google Analytics',
        'features'               => 'Features',
    ),

    /*
    |--------------------------------------------------------------------------
    | FAQ Questions Language Lines
    |--------------------------------------------------------------------------
    |
    | The following lines are questions displayed on the FAQ page. Each question
    | is listed under a section defined above. Questions should be numbered so
    | that the appropriate answer may be found in the answers array.
    |
    */

    'questions' => array(
        'account-info' => array(
            'q1' => 'How do I change the email address associated with this account?',
            'q2' => 'I entered my Username incorrectly (or changed it on Pinterest recently).
                     How do I change my Username for this account?',
            'q3' => 'I want to track another Pinterest profile. How do I add an account?',
            'q4' => 'How do I cancel my account?',
        ),
        'billing' => array(
            'q1' => 'How do I change the credit card associated with this account?',
            'q2' => 'My company prefers to be invoiced. How do I set that up?',
            'q3' => 'I need a reciept/statement. How do I get it?',
            'q4' => 'Do I have to sign a contract?',
        ),
        'collaborators' => array(
            'q1' => 'What is a collaborator?',
            'q2' => 'How do I add a collaborator?',
            'q3' => 'Can a collaborator change information on my account?',
        ),
        'tailwind-terms-defined' => array(
            'q1' => 'Followers',
            'q2' => 'Pins',
            'q3' => 'Repins',
            'q4' => 'Likes',
            'q5' => 'Virality Score',
            'q6' => 'Engagement Score',
            'q7' => 'Engagement Rate',
            'q8' => 'Reach',
            'q9' => 'History',
        ),
        'competitors' => array(
            'q1' => 'How do I add Competitors?',
            'q2' => 'Do Competitors get notified when I start tracking them?',
        ),
        'google-analytics' => array(
            'q1' => 'How do I sync my Google Analytics?',
            'q2' => 'Do you support other ROI analytics platforms besides Google Analytics
                     (Omniture and others)?',
        ),
        'features' => array(
            'q1' => 'Can I export data from my dashboard?',
            'q2' => 'Do you have dedicated support?',
            'q3' => 'Do you have a contest management platform?',
            'q4' => 'Do you offer pin scheduling?',
            'q5' => 'Do you support other platforms besides Pinterest?',
            'q6' => 'Can I get a walkthrough of the dashboard?',
        ),
    ),

    /*
    |--------------------------------------------------------------------------
    | FAQ Answers Language Lines
    |--------------------------------------------------------------------------
    |
    | The following lines are answers to FAQ page questions. Each answer is
    | listed under a section defined above. Answers should be numbered to match
    | their corresponding question (q1/a1).
    |
    */

    'answers' => array(
        'account-info' => array(
            'a1' => "For now, we need to do this for you. <a id=\"Intercom\">Send us a message</a>
                     letting us know the current and new email address.",
            'a2' => "Click on the cog in the upper right corner of your dashboard and select
                     \"Account Settings.\" This will take you to the Account Management tab where
                     you'll see the \"Username\" field on your account. Simply delete what you have
                     currently and enter the correct Pinterest Username. Usernames come after
                     \"pinterest.com/\" in the URL of your Pinterest profile. Our profile is at
                     \"http://pinterest.com/Tailwind\", so Tailwind is our username.",
            'a3' => "Click on the cog in the upper right corner of your dashboard and select
                     \"Account Settings.\" This will take you to the Account Management tab where
                     you'll see your current account information and green text that says \"Add
                     Another Account.\" Click on it and you will be given another account fields
                     tab. Enter the name of the account (call it whatever you'd like), the domain
                     and the Pinterest Username (be sure to doublecheck this one so you can be sure
                     we're pulling the correct data. Usernames are found by going to their
                     Pinterest profile and checking the address bar. Use what comes after the
                     \"pinterest.com/\" For instance, our Pinterest username is \"Tailwind\"- if
                     you were on our profile, you'd see \"pinterest.com/Tailwind\" in the address
                     bar). You can also select the Account Type and Industry which are helpful as
                     we are working on an industry benchmarking tool to let you know how your
                     Pinterest presence measures up against industry standards. Click the \"Add to
                     Dashboard\" button to complete the setup.",
            'a4' => "Awww. We hate to see you go! You have two options:  
                     <ol>
                         <li>
                             Downgrade your account to the Free Level so you can continue using the
                             free version of Tailwind. This will ensure we continue collecting data
                             for your account, so you can have a richer historical archive should
                             you upgrade again in the future.   <a href=\"/upgrade?ref=faq\">Click
                             here to change your plan.</a>
                         </li>
                         <li>
                             You can cancel your account altogether. WARNING: Canceling will wipe
                             your historical data and cease data collection on your account. This
                             data will not be recoverable. To cancel, just <a id=\"Intercom\">send
                             us a message</a> letting us know you'd like to cancel. We'll reach out
                             for details necessary to take care of it for you.
                         </li>
                     </ol>",
        ),
        'billing' => array(
            'a1' => "Click on the cog in the upper right corner of your dashboard and select
                     \"Billing.\" Then, click the blue \"Update Billing Info\" button. You'll be
                     taken to a secure page to update your credit card information.",
            'a2' => "Just click on the quote bubbles to chat or <a id=\"Intercom\">send us a
                     message</a> letting us know you'd like to be invoiced instead. We'll reach out
                     for details and get it set up for you!",
            'a3' => "You can access your statements by clicking on the cog in the upper right
                     corner of your dashboard and selecting \"Billing.\" Then, just click on the
                     \"Statements\" tab and you should be taken to a list of your monthly
                     statements since you began service with us.",
            'a4' => "Not at all. We're happy to have you on a monthly plan without locking you into
                     a contract. However, if you'd like to discuss the added benefits of paying
                     quarterly or yearly, feel free to reach out.",
        ),
        'collaborators' => array(
            'a1' => "A collaborator is anyone you'd like to share your dashboard with: clients,
                     managers, the rest of your team... When you add a collaborator, that person
                     gets their own login information so that they can check on the brand's
                     Pinterest progress any time they'd like.",
            'a2' => "Click on the \"Add a collaborator\" button at the top of your dashboard. You
                     will be taken to the Collaborators page of your profile where you can manage
                     access to your dashboard. To add a collaborator, simply fill in their name and
                     email and select the role (viewer or admin) you'd like to give them. Then, hit
                     the \"Invite Collaborator\" button. They will be sent an email inviting them
                     to sign into the dashboard.",
            'a3' => "When you add a new collaborator, you get the choice of making them either a
                     \"viewer\" or an \"admin.\" A collaborator labeled as a \"viewer\" can only
                     view the dashboard. They don't have permissions to make any changes to the
                     account. If you label them as \"admin,\" however, they have the same
                     permissions as you to change account information.",
        ),
        'tailwind-terms-defined' => array(
            'a1' => "A follower is a user on Pinterest who is following your whole profile or just
                     a board or two. The number under \"Followers\" is how many users follow your
                     Pinterest profile.",
            'a2' => "A pin is a piece of content users have posted to Pinterest. The number under
                     \"Pins\" is how many pieces of content you have pinned to your profile (this
                     includes anything you've repinned to your profile).",
            'a3' => "A repin is when a user re-posts a piece of content on Pinterest. The number
                     under \"Repins\" is the number of times that the pins on your profile have
                     received repins.",
            'a4' => "A like is when a user clicks the \"heart\" button on a pin to show they like
                     it rather than repinning it. The number under \"Likes\" is the number of likes
                     that the pins on your profile have received.",
            'a5' => "Virality Score is measured by the Total Repins divided by Total Pins. This
                     number represents how much your content is getting repinned across your
                     profile.",
            'a6' => "Engagement Score is measured by the Total Repins divided by Total Pins divided
                     by Total Followers. This number is a measure of your Audience's Engagement
                     with your pins, giving you insight into how much interaction your pins are
                     receiving from each follower.",
            'a7' => "Engagement Rate is measured by counting how many of your pins have at least
                     one repin vs. those that have no repins (represented as a percentage of the
                     whole). It's a simple way for you to see how well your content is resonating
                     with your audience.",
            'a8' => "Reach is measured by the number of followers a user has multiplied by the
                     number of times they have pinned from your domain (or repinned content from
                     your profile on the \"Top Repinners\" tab). This number represents how many
                     times your content may have surfaced in front of fans after being pinned. The
                     more Influential the pinner, and the more they have pinned from your domain
                     (or repinned your content- \"Top Repinners\" tab), the bigger the potential
                     for larger Reach.",
            'a9' => "We provide accurate historical data on pins and comments going back to the day
                     you started your Pinterest account. Some of your historical metrics from before
                     you signed up (such as repin, like and follower counts) may also be estimated
                     using trend data to give you the most accurate view possible. Estimated
                     historical data will be clearly marked in your charts so you can easily tell
                     the difference.",
        ),
        'competitors' => array(
            'a1' => "Click on the settings cog in the upper right corner of your dashboard and
                     select \"Manage Competitors.\" You will be taken to the Competitor management
                     tab. Here, you can add a competitor by simply giving them a Name (call them
                     what you want, we don't mind), enter their Domain and their Pinterest Username
                     (be sure to doublecheck this one so you can be sure we're pulling the correct
                     data). Usernames are found by going to their Pinterest profile and checking
                     the address bar. Use what comes after the \"pinterest.com/\" For instance, our
                     Pinterest username is \"Tailwind\"- if you were on our profile, you'd see
                     \"pinterest.com/Tailwind\" in the address bar).",
            'a2' => "NOPE. We believe in letting you live out your dream of being a Private Eye.
                     Don't worry, we won't let them know you're spying.",
        ),
        'google-analytics' => array(
            'a1' => "Click the settings cog in the upper right corner and select
                     \"Sync Google Analytics.\" You'll be taken to the Google Analytics management
                     tab. From there it's just 3 easy steps:
                     <ol>
                        <li>
                            Sync your account by simply clicking the Big Blue \"Integrate Google
                            Analytics\".
                        </li>
                        <li>
                            You'll then be asked to login to your Google account and approve the
                            integration.
                        </li>
                        <li>
                            Once the integration is complete, you'll have to choose a Google
                            Analytics \"profile\" that you would like to associate with Tailwind.
                        </li>
                     </ol>
                     After choosing your profile, give the dashboard some time to pull in your data
                     (usually takes between 1-2 hours) and your Traffic report will begin to fill
                     in with data.  If you are tracking revenue via Google Analytics, and you're on
                     a Professional or Enterprise account, you'll also have access to your \"Most
                     Valuable Pinners\" report, which will be ready on the following day.",
            'a2' => "At the moment, users can only sync Google Analytics with our Dashboard. We're
                     looking into other platforms to see how they could fit in the future. Please
                     let us know if you'd like us to support another analytics platform. Your
                     feedback is what will help mold our roadmap.",
        ),
        'features' => array(
            'a1' => "Yes. With a Professional plan, almost all of the data on the dashboard is
                     exportable. You can export it into a .csv or print it how it appears on the
                     dashboard.",
            'a2' => "On our Enterprise plans, we offer dedicated support. On lower level plans, you
                     can always reach us via in-app chat or email!",
            'a3' => "Not at the moment. Reach out to us if you'd like to discuss future plans for
                     such a feature.",
            'a4' => "Not yet.... Reach out to us if you'd like to discuss future plans for such a
                     feature.",
            'a5' => "Currently, we focus on providing the deepest analytics available for
                     Pinterest. However, we are exploring possibilities with other platforms.",
            'a6' => "Sure! We offer live webinars every week. <a
                     href=\"https://attendee.gotowebinar.com/rt/7480801490469400320\"
                     target=\"_blank\">(Click here to choose a session you'd like to attend)</a>.
                     Or, if you'd like a one-on-one walkthrough, just click on the quote bubbles to
                     chat or <a id=\"Intercom\">send us a message</a> letting us know you'd like to
                     set up a demo. We'll reach out for details and get it set up for you!",
        ),
    ),
);