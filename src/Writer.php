<?php
namespace hiddenpathz\changelogWriter;

use Exception;

class Writer
{
    public function __construct(array $arguments = [])
    {
        $this->isError = false;

        $this->bindLastTag();
        $this->bindLastCommit();
        $this->bindRepoLink($arguments);
    }

    /**
     * @var string
     */
    public $repoLink = '';

    /**
     * @var string
     */
    public $branchName = '';

    /**
     * @var int
     */
    private $majorTag;

    /**
     * @var int
     */
    private $minorTag;

    /**
     * @var int
     */
    private $hotfixTag;

    /**
     * @var string
     */
    private $masterCommit;

    /**
     * @var string
     */
    private $newTag;

    /**
     * @var array
     */
    private $changes = [];

    /**
     * @var string
     */
    private $answerBody;

    /**
     * @var string
     */
    private $answerTitle;

    /**
     * @var bool
     */
    private $isError;

    /**
     * @param array $arguments
     * @return void
     */
    private function bindRepoLink(array $arguments): void
    {
        if (isset($arguments[1]) === true) {

            $this->repoLink  = $arguments[1];

            return;
        }

        preg_match('/REPOSITORY_LINK=(\S+)/', file_get_contents('./.env'), $matches);

        $this->repoLink = empty($matches) === false ? $matches[1] : 'http://';
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        try {
            $this->beforeChange();

            $this->fill();
            $this->createSign();
            $this->createCommit();

            $this->afterChange();

        } catch (\Exception $e) {

            $this->printMessage("Ошибка: " . $e->getMessage() . "\n", 31);

            return;
        }
    }

    /**
     * @return array
     */
    private function getChangeType(): array
    {
        return [
            'feat'      => 'Реализовано',
            'refactor'  => 'Изменено',
            'fix'       => 'Исправлено',
            'remove'    => 'Удалено'
        ];
    }

    /**
     * @return array
     */
    private function getChangeTag(): array
    {
        return [
            1 => $this->majorTag + 1 . '.0.0',
            2 => $this->majorTag . '.' . ($this->minorTag + 1) . '.0',
            3 => $this->majorTag . '.' . $this->minorTag . '.' . ($this->hotfixTag + 1),
        ];
    }

    /**
     * @return void
     * @throws Exception
     */
    private function fill(): void
    {
        if ($this->isError === true) {
            return;
        }

        $this->printMessage("Текущая версия приложения: " . $this->getLastTag() . "\n", 33);

        $this->printMessage("Какую версию нужно поднять?\n 1 - major (*.0.0)\n 2 - minor (0.*.0)\n 3 - fix   (0.0.*)  - ");

        $level = trim(fgets(STDIN));

        if (in_array($level, ['major', 'minor', 'fix']) === true) {

            throw new Exception('Неизвестное значение');

        }

        $this->newTag = $this->getChangeTag()[$level];

        $this->printMessage("Следующая версия приложения: " . $this->newTag . " \n", 32);

        $this->generate();


        if (empty($this->answerBody) === true) {

            throw new Exception('Отсутствуют коммиты с нужными тэгами');
        }

        $this->printMessage("Изменения которые попадут в CHANGELOG.md: \n" . $this->answerBody . " \n", 33);

    }

    /**
     * @return void
     * @throws Exception
     */
    private function createSign(): void
    {
        $this->question('Все верно?');

        try {

            $this->print();

        } catch (\Throwable $e) {

            $this->printMessage("Во время записи произошла ошибка. Причина: " . $e->getMessage() . "\n", 31);
        }

        $this->printMessage("Файл CHANGELOG.md успешно отредактирован:  \n", 32);
    }

    /**
     * @return void
     * @throws Exception
     */
    private function createCommit(): void
    {
        $this->question('Создать коммит?');

        try {

            $this->commit();

        } catch (\Throwable $e) {

            $this->printMessage("Во время создания коммита произошла ошибка. Причина: " . $e->getMessage() . "\n", 31);
        }

        $this->printMessage("Коммит успешно создан!  \n", 32);
    }

    /**
     * @return void
     */
    private function commit(): void
    {
        $commands = [
            'git add ./CHANGELOG.md',
            "git commit -m 'Отредактирован CHANGELOG.md'"
        ];

        exec(implode(' && ', $commands));
    }

    /**
     * @return void
     */
    public function print(): void
    {
        $filePath = './CHANGELOG.md';

        $content = file_get_contents($filePath);

        $pos = strpos($content, '## [');

        $insertStr = $this->answerTitle . $this->answerBody;

        $newData = substr_replace($content, "{$insertStr}\n", $pos, 0);

        file_put_contents($filePath, $newData);
    }

    /**
     * @return void
     */
    public function generate(): void
    {
        foreach ($this->getChanges() as $line) {

            list($commit, $author, $message, $date) = explode('|', $line, 4);

            preg_match("/\[(.*)]\s(\w+):\s(.*)/", $message, $matches);

            if (empty($matches) === true) {
                continue;
            }

            $this->changes[$this->getChangeType()[$matches[2]]][] = trim($matches[3]);
        }

        $this->bindAnswer();
    }

    /**
     * @return void
     */
    private function bindAnswer(): void
    {
        $this->answerTitle = '## [ [' . $this->newTag . '](' . $this->repoLink . $this->newTag . ') ] - ' .
            date('d.m.Y') . PHP_EOL . PHP_EOL;

        $this->answerBody = '';

        foreach ($this->changes as $key => $type) {

            $this->answerBody .= '- ' . $key . ':' . PHP_EOL;

            foreach ($type as $elem) {
                $this->answerBody .= "  - " . $elem . PHP_EOL;
            }
        }
    }

    /**
     * @return void
     */
    private function bindLastTag(): void
    {
        $commands = [
            'git describe --tags $(git rev-list --tags --max-count=1)'
        ];

        exec(implode(' && ', $commands), $lastTag);

        if (empty($lastTag) === true) {

            $this->printMessage("Отсутствует последний тэг\n", 31);
            $this->isError = true;
            return;
        }

        list($this->majorTag, $this->minorTag, $this->hotfixTag) = explode('.', current($lastTag));
    }

    /**
     * @return void
     */
    private function bindLastCommit(): void
    {
        exec('git rev-parse origin/master', $lastMasterCommit);

        $this->masterCommit = current($lastMasterCommit);
    }

    /**
     * @return string
     */
    private function getLastTag(): string
    {
        return $this->majorTag . '.' . $this->minorTag . '.' . $this->hotfixTag;
    }

    /**
     * @return array
     */
    private function getChanges(): array
    {
        $commands = [
            'git log --pretty=format:"%h|%an|%s|%cs" --no-merges ' . $this->getLastTag() . '..develop',
            'git cherry -v ' . $this->masterCommit . ' ' . $this->getLastTag(),
        ];

        exec(implode(' && ', $commands), $output);

        return $output;
    }

    /**
     * @param string $message
     * @param int $color
     * @return void
     */
    private function printMessage(string $message, int $color = 0): void
    {
        fwrite(\STDOUT, "\033[01;" . $color . "m" . $message . "\033[0m");
    }

    /**
     * @return void
     * @throws Exception
     */
    public function beforeChange(): void
    {
        $this->generateBranchName();
        $this->createBranch();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function afterChange(): void
    {
        $this->question('Пушить ветку ' . $this->branchName . '?');
        $this->pushChanges();
        $this->deleteBranch();
    }

    /**
     * @return void
     */
    private function generateBranchName()
    {
        preg_match('/BRANCH_PREFIX=(\S+)/', file_get_contents('./.env'), $matches);

        $prefix = empty($matches) === false ? $matches[1] . '-' : '';

        $this->branchName = 'hotfix/' . $prefix . date('dmY').'-assign-to-changelog';
    }

    /**
     * @return void
     * @throws Exception
     */
    private function createBranch()
    {
        exec('git branch --list w ' . $this->branchName, $result);

        if (count($result) > 0) {

            $this->printMessage("Нужная ветка уже существует! Создавать не нужно  \n", 32);

            system('git checkout ' . $this->branchName, $result);

            return;
        }

        $commands = [
            'git checkout develop',
            'git checkout -b ' . $this->branchName,
        ];

        system(implode(' && ', $commands), $result);

        if ($result !== 0) {
            throw new Exception('Не удалось выполнить создание ветки');
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function pushChanges()
    {
        system('git push origin ' . $this->branchName, $result);

        if ($result !== 0) {
            throw new Exception('Не удалось выполнить push');
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function deleteBranch()
    {
        $commands = [
            'git checkout develop',
            'git branch -D ' . $this->branchName,
        ];

        system(implode(' && ', $commands), $result);

        if ($result !== 0) {
            throw new Exception('Не удалось выполнить удаление ветки');
        }
    }

    /**
     * @param string $question
     * @return void
     * @throws Exception
     */
    private function question(string $question): void
    {
        $confirmation = readline($question . ' (y/n): ');

        if (strtolower($confirmation) !== 'yes' && strtolower($confirmation) !== 'y') {

            throw new Exception('Выполнение команды отменено');
        }
    }
}