@if (empty($name))
    <h3>Hi there!</h3>
@else
    <h3>Hey {{$name}}!</h3>
@endif

<p>Just now, {{$nameAdded}} added you to {{$houseName}} on My Student House.</p>

<p>Create an account or login on <b>https://mystudent.house</b></p>