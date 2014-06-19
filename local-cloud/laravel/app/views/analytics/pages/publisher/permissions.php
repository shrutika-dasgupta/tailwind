<?= $navigation ?>

<div class="row-fluid" id="publisher-permissions">
    <div class="span12">

        <?php if ($users->count() == 1): ?>
            <div class="hero-unit">
                <h2>Publisher plays nice with teams.</h2>
                <p>Go invite some collaborators to get started!</p>
                <p>
                    <a href="<?= URL::route('collaborators') ?>" class="btn btn-info btn-large">Invite Collaborators</a>
                </p>
            </div>
        <?php endif ?>

        <h3>Team Permissions</h3>

        <form action="<?= URL::route('publisher-update-permissions') ?>" method="post">

            <table class="table table-striped table-hover">

                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>
                            Role
                            <a class="publisher-permissions-help"
                               data-toggle="popover"
                               data-container="body"
                               data-placement="<?= $users->count() == 1 ? 'top' : 'bottom' ?>"
                               data-original-title="&lt;strong&gt;Publisher Roles&lt;/strong&gt;"
                               data-content="
                                   &lt;strong&gt;Admin&lt;/strong&gt;
                                   &lt;p&gt;
                                       Admins have viewing and editing permissions across all of pin
                                       scheduling. Any post created by an Admin will go straight to
                                       your scheduled posts. Admins also approve posts submitted by
                                       Draft Editors.
                                   &lt;/p&gt;
                                   &lt;strong&gt;Draft Editor&lt;/strong&gt;
                                   &lt;p&gt;
                                       Draft Editors have access to view your scheduled times and
                                       posts, but may only create and edit draft posts. Any post
                                       submitted by a Draft Editor will be sent to an approval queue
                                       where it must be approved by an Admin before it enters your
                                       scheduled posts.
                                   &lt;/p&gt;
                               "
                            >
                                <i class="icon-help"></i>
                            </a>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user->getName() ?></td>
                            <td><?= $user->email ?></td>
                            <td>
                                <input type="checkbox"
                                       class="publisher-role-switch"
                                       name="user_roles[<?= $user->cust_id ?>]"
                                       value="admin"
                                    <?php if ($user->hasFeature('pin_scheduling_admin')): ?>
                                        checked
                                    <?php endif ?>
                                    <?php if ($user->cust_id == $current_user->cust_id): ?>
                                        readonly
                                    <?php endif ?>
                                />

                                <?php if ($user->cust_id == $current_user->cust_id): ?>
                                    <span class="badge">This is you!</span>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>

            </table>

            <?php if ($users->count() > 1): ?>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Update Permissions</button>
                </div>
            <?php endif ?>
        </form>
    </div>
</div>