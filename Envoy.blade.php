@servers(['web' => '<serverUsername>@<SERVER_IP_ADDRESS>', 'local' => '127.0.0.1'])

@setup
    $release = date('YmdHis');
    $keep = 3; // the # of releases to keep (n-1)
    $userName = 'www-data'; // good for nginx
    $groupName = 'www-data'; // good for nginx
    $projects = [];
    $localProjectFolder = '~/Sites'; // update accordingly - I use Laravel Valet

    $localProjects = ['my-local-project-2-that-needs-updating-also'];

    $projectA = [
        'name'          => 'Project A',
        'repository'    => 'git@gitlab.com:<USERNAME>/project-a.git',
        'releases_dir'  => '/var/www/project-a/releases',
        'app_dir'       => '/var/www/project-a',
        'app_live_dir'  => 'html',
        'release'       => $release,
        'new_release_dir' => '/var/www/project-a/releases/' . $release,
    ];

    $projectB = [
        'name'          => 'Project N',
        'repository'    => 'git@gitlab.com:<USERNAME>/project-n.git',
        'releases_dir'  => '/var/www/project-n/releases',
        'app_dir'       => '/var/www/project-n',
        'app_live_dir'  => 'app',
        'release'       => $release,
        'new_release_dir' => '/var/www/project-n/releases/' . $release,
    ];

    // include only those projects that need updating
    array_push($projects, $projectA);
    array_push($projects, $projectN);
    // ....

@endsetup

@story('deploy')
    clone_repository
    run_composer
    update_symlinks
    update_local
@endstory

@task('clone_repository', ['on' => 'web'])

    @if(count($projects) > 0)

        @foreach($projects as $item)
        echo '>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>'
        echo 'Project: {{ $item['name'] }}'
        echo '------------------------------------------'

        echo 'Cleaning previous releases but leave the latest {{ $keep - 1 }} intact'
        [ -d {{ $item['releases_dir'] }} ] || mkdir {{ $item['releases_dir'] }}
        cd {{ $item['releases_dir'] }}
        pwd
        ls
        ls -t | tail -n +{{ $keep }} | xargs -I {} rm -rf {}
        echo '----- Releases left ------'
        ls

        echo 'Cloning repository'
        git clone --depth 1 {{ $item['repository'] }} {{ $item['new_release_dir'] }}

        cd {{ $item['new_release_dir'] }}
        echo 'Switched to release directory'
        pwd
        git reset --hard {{ $commit }}
    @endforeach

    @endif

@endtask

@task('run_composer', ['on' => 'web'])

    @if(count($projects) > 0)

        @foreach($projects as $item)
        echo '>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>'
        echo 'Project: {{ $item['name'] }}'
        echo '------------------------------------------'
        echo "Starting deployment ({{ $release }})"
        cd {{ $item['new_release_dir'] }}
        composer install --prefer-dist --no-scripts -q -o
    @endforeach

    @endif

@endtask

@task('update_symlinks', ['on' => 'web'])

    @if(count($projects) > 0)

        @foreach($projects as $item)
            echo '>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>'
            echo 'Project: {{ $item['name'] }}'
            echo '------------------------------------------'

            echo "Linking storage directory"
            rm -rf {{ $item['new_release_dir'] }}/storage
            ln -nfs {{ $item['app_dir'] }}/storage {{ $item['new_release_dir'] }}/storage

            echo 'Linking .env file'
            ln -nfs {{ $item['app_dir'] }}/.env {{ $item['new_release_dir'] }}/.env

            echo 'Ascertain live dir is absent'
            cd {{ $item['app_dir'] }}
            pwd
            echo 'Removing {{ $item['app_live_dir'] }} folder if it exists'
            echo "{{ $password }}" | sudo -S rm -rf {{ $item['app_live_dir'] }}
            ls -la

            echo 'Linking current release'
            ln -nfs {{ $item['new_release_dir'] }} {{ $item['app_dir'] }}/{{ $item['app_live_dir'] }}

            echo 'Ascertain permissions are correct'
            @if ($password)
                echo "Password provided: {{ $password }}"
                echo "{{ $password }}" | sudo -S chown -R {{ $userName . ':' . $groupName }} {{ $item['app_live_dir'] }}
                echo "{{ $password }}" | sudo -S chown -R {{ $userName . ':' . $groupName }} {{ $item['new_release_dir'] }}
            @else
                echo "PASSWORD NOT PROVIDED! Please ensure you have provided --password=secret"
            @endif

            echo '**************************************************'
            echo 'Listing contents for current release folder ({{ $item['name'] }})'
            ls -la {{ $item['new_release_dir'] }}

            echo '**************************************************'
            echo 'Listing contents for live website ({{ $item['name'] }})'
            ls -la
            ls -la {{ $item['app_live_dir'] }}
        @endforeach

    @endif

@endtask

@task('update_local', ['on' => 'local'])

    @if(count($localProjects) > 0)

        @foreach($localProjects as $item)
            echo '>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>'
            echo 'Local Project: {{ $item }}'
            echo '------------------------------------------'

            cd {{ $localProjectFolder }}/{{ $item }}
            pwd

            echo 'Pulling latest commit on existing branch'
            git pull

            echo 'Running composer install'
            composer install

            echo 'Running npm install'
            @if ($npm)
                npm install
            @else
                echo "NPM INSTALL NOT SPECIFIED! Please ensure you have provided --npm=true"
            @endif

            echo 'DONE!'
        @endforeach

    @else
        echo 'No local projects defined'
    @endif

@endtask

@story('list')
    list_web
    list_local
@endstory

@task('list_web', ['on' => 'web'])

    @if(count($projects) > 0)

        @foreach($projects as $item)
        echo '>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>'
        echo 'Project: {{ $item['name'] }}'
        echo '------------------------------------------'
        cd {{ $item['app_dir'] }}
        echo 'Listing contents for app ({{ $item['app_dir'] }}) folder'
        ls -la

        cd {{ $item['app_live_dir'] }}
        echo 'Listing contents for Live ({{ $item['app_live_dir'] }}) folder'
        ls -la

        cd {{ $item['releases_dir'] }}
        echo 'Listing contents for Releases ({{ $item['releases_dir'] }}) folder'
        ls -la
    @endforeach

    @else
        echo 'No projects defined'
    @endif

@endtask

@task('list_local', ['on' => 'local'])

    @if(count($localProjects) > 0)

        @foreach($localProjects as $item)
            echo '>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>'
            echo 'Local Project: {{ $item }}'
            echo '------------------------------------------'
            cd {{ $localProjectFolder }}/{{ $item }}
            echo 'Listing contents for app ({{ $item }}) folder'
            ls -la
        @endforeach

    @endif

@endtask

@finished
    @slack('slack-webhook', '#bots')
@endfinished
