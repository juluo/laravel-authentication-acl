<?php  namespace Jacopo\Authentication\Controllers;
/**
 * Class GroupController
 *
 * @author jacopo beschi jacopo@jacopobeschi.com
 */
use Illuminate\Support\MessageBag;
use Jacopo\Authentication\Presenters\GroupPresenter;
use Jacopo\Library\Form\FormModel;
use Jacopo\Authentication\Helpers\FormHelper;
use Jacopo\Authentication\Models\Group;
use Jacopo\Authentication\Exceptions\UserNotFoundException;
use Jacopo\Authentication\Validators\GroupValidator;
use Jacopo\Library\Exceptions\JacopoExceptionsInterface;
use View, Input, Redirect, App;

class GroupController extends \Controller
{
    /**
     * @var \Jacopo\Authentication\Repository\SentryGroupRepository
     */
    protected $group_repository;
    /**
     * @var \Jacopo\Authentication\Validators\GroupValidator
     */
    protected $group_validator;
    /**
     * @var FormHelper
     */
    protected $form_model;

    public function __construct(GroupValidator $v, FormHelper $fh)
    {
        $this->group_repository = App::make('group_repository');
        $this->group_validator = $v;
        $this->f = new FormModel($this->group_validator, $this->group_repository);
        $this->form_model = $fh;
    }

    public function getList()
    {
        $groups = $this->group_repository->all(Input::all());

        return View::make('authentication::group.list')->with(["groups" => $groups]);
    }

    public function editGroup()
    {
        try
        {
            $obj = $this->group_repository->find(Input::get('id'));
        }
        catch(UserNotFoundException $e)
        {
            $obj = new Group;
        }
        $presenter = new GroupPresenter($obj);

        return View::make('authentication::group.edit')->with(["group" => $obj, "presenter" => $presenter]);
    }

    public function postEditGroup()
    {
        $id = Input::get('id');

        try
        {
            $obj = $this->f->process(Input::all());
        }
        catch(JacopoExceptionsInterface $e)
        {
            $errors = $this->f->getErrors();
            // passing the id incase fails editing an already existing item
            return Redirect::route("users.groups.edit", $id ? ["id" => $id]: [])->withInput()->withErrors($errors);
        }
        return Redirect::action('Jacopo\Authentication\Controllers\GroupController@editGroup',["id" => $obj->id])->withMessage("Gruppo modificato con successo.");
    }

    public function deleteGroup()
    {
        try
        {
            $this->f->delete(Input::all());
        }
        catch(JacopoExceptionsInterface $e)
        {
            $errors = $this->f->getErrors();
            return Redirect::action('Jacopo\Authentication\Controllers\GroupController@getList')->withErrors($errors);
        }
        return Redirect::action('Jacopo\Authentication\Controllers\GroupController@getList')->withMessage("Gruppo cancellato con successo.");
    }

    public function editPermission()
    {
        // prepare input
        $input = Input::all();
        $operation = Input::get('operation');
        $this->form_model->prepareSentryPermissionInput($input, $operation);
        $id = Input::get('id');

        try
        {
            $obj = $this->group_repository->update($id, $input);
        }
        catch(JacopoExceptionsInterface $e)
        {
            return Redirect::route("users.groups.edit")->withInput()->withErrors(new MessageBag(["permissions" => "Permesso non trovato"]));
        }
        return Redirect::action('Jacopo\Authentication\Controllers\GroupController@editGroup',["id" => $obj->id])->withMessage("Permesso modificato con successo.");
    }
}