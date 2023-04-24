<?php
namespace efko\changelogWriter;

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
        $this->repoLink = $arguments[1] ?: getenv('REPOSITORY_LINK');
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
     */
    public function fill(): void
    {
        if ($this->isError === true) {
            return;
        }

        $this->printMessage("Текущая версия приложения: " . $this->getLastTag() . "\n", 33);

        $this->printMessage("Какую версию нужно поднять?\n 1 - major (*.0.0)\n 2 - minor (0.*.0)\n 3 - fix   (0.0.*)  - ");

        $level = trim(fgets(STDIN));

        if (in_array($level, [1, 2, 3]) === true) {

            $this->printMessage("Неизвестное значение.\n", 31);

            return;
        }

        $this->newTag = $this->getChangeTag()[$level];

        $this->printMessage("Следующая версия приложения: " . $this->newTag . " \n", 32);

        $this->generate();


        if (empty($this->answerBody) === true) {

            $this->printMessage("Отсутствуют коммиты с нужными тэгами \n", 31);

            return;
        }

        $this->printMessage("Изменения которые попадут в CHANGELOG.md: \n" . $this->answerBody . " \n", 33);

        $confirmation = readline('Все верно? (y/n): ');

        if (strtolower($confirmation) !== 'yes' && strtolower($confirmation) !== 'y') {

            $this->printMessage("Выполнение команды отменено\n", 31);

            return;
        }

        try {

            $this->print();

        } catch (\Throwable $e) {

            $this->printMessage("Во время записи произошла ошибка. Причина: " . $e->getMessage() . "\n", 31);
        }

        $this->printMessage("Файл CHANGELOG.md успешно отредактирован:  \n", 32);
    }

    /**
     * @return void
     */
    public function print(): void
    {
        $filePath = './CHANGELOG.md';
        $title = "# История изменений\n\n";

        $content = file_get_contents($filePath);
        $content = preg_replace('/^' . $title . '/', '', $content);

        $newLine = $title . $this->answerTitle . $this->answerBody . "\n";

        file_put_contents($filePath, $newLine . $content);
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
        $this->answerTitle = '## [ [' . $this->newTag . '](' . $this->repoLink . '/-/tags/' . $this->newTag . ') ] - ' .
            date('d.m.Y') . PHP_EOL;

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
        exec('git describe --tags $(git rev-list --tags --max-count=1)', $lastTag);

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

}