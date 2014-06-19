<?php

    namespace Admin;
    use View;
    use Auth;

    class DashboardController extends BaseController
    {

        /*
         * The layout that should be used for responses.
         */
        protected $layout = 'layouts.admin';

        /**
         * Shows the home page
         *
         * @author  Will
         */
        public function showIndex()
        {
            $this->layout->user_name = Auth::user()->email;
            $this->layout->main_content = View::make('admin.server.dataengine_cpu');

        }
    }