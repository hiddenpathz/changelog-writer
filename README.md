# ChangeLog Writer

ChangeLog Writer - это инструмент, который позволяет автоматически генерировать файл Changelog в вашем проекте.

## Требования к окружению

Для работы ChangeLog Writer требуется PHP версии 7.3.0 и выше.

В приложении:

Контроль версий GIT ветки Develop и Master
Файл CHANGELOG.md в корне

## Установка

Для начала работы с ChangeLog Writer выполните следующие шаги:

1. Установите пакет с помощью Composer:

   ```bash
   composer global require hiddenpathz/changelog-writer
   ```
2. Добавим ссылку в систему

    Для Linux
   ```bash
   sudo ln -s ~/.config/composer/vendor/hiddenpathz/changelog-writer/src/bin /usr/bin/changeloger
   ```
   
    Для MacOS 
   ```bash
   sudo ln -s ~/.composer/vendor/hiddenpathz/changelog-writer/src/bin /usr/local/bin/changeloger
   ```

3. Changeloger готов к работе. Находясь в папке своего проекта достаточно вызвать его по ссылке которую создали.


## Подготовка проекта для работы 

Добавьте в свой ENV файл 
* переменную с ссылкой на ваш репозиторий до номера тега
* префикс ветки (опционально, зависит от правил):
* Название системы поставщика задач (опционально, зависит от правил, если нужно чтобы в ченжлоге были ссылки на задачи):
* Ссылку на систему поставщика задач до кода заявки (опционально, зависит от правил, если нужно чтобы в ченжлоге были ссылки на задачи):

   ```bash
   REPOSITORY_LINK=https://gitlab.some.ru/your.repo.ru//-/tags/
   BRANCH_PREFIX=MYPROJECT
   TASK_SYSTEM_NAME=SomeTaskSystemName
   TASK_SYSTEM_LINK=https://some-task-system.ru/tasks/view?code=
   ```
  
* Если файл CHANGELOG.md находится на разных уровнях с файлом .env, необходимо выполнять команду в каталоге с .env, а также добавить переменную:

   ```bash
   CHANGELOG_PATH=../CHANGELOG.md
   ```

Ваша консольная команда для запуска ChangeLog Writer выглядит следующим образом:

   ```bash
       changeloger
       
       changeloger https://gitlab.some.ru/your.repo.ru//-/tags/
   ```

В случае указания пути в атрибуте, именно он будет использован в файле.

## Использование

* При старте вы будете автоматически переключены на ветку develop.

* Программа запросит ввести номер заявки и задачи, для именования ветки в формате IU000000-W0111111.

* От нее создастся ветка вида:
   "feature/MYPROJECT-IU000000-W0111111-assign-to-changelog" (Если в env указан префикс)
   "feature/IU000000-W0111111-assign-to-changelog" (Если в env не указан префикс)

* Далее выведется текущая версия приложения:

   ```bash
   Текущая версия приложения: 1.24.5
   Какую версию нужно поднять?
   1 - major (*.0.0)
   2 - minor (0.*.0)
   3 - fix   (0.0.*)
   ```
* После выбора следующей версии приложения скрипт выведет все коммиты подходящие под правила именования.

Удостоверьтесь, что ваш коммиты содержат ключевые слова в сообщении, для того чтобы они были отображены в файле Changelog.

| Ключ     | Запись      |
|----------|-------------|
| feat     | Реализовано |
| refactor | Изменено    |
| fix      | Исправлено  |
| remove   | Удалено     |

Обработка включает ключи, которые не будут отображены в CHANGELOG.md

```md
   wip - (work in process) - для промежуточных коммитов в процессе работы
   ci - Коммиты с настройками CI/CD
   build - Коммиты с настройками окружения, которые не несут смысловой нагрузки для юзеров и не должны отображаться в описании изменений
```


* Пример коммита который попадет в changelog.

   ```bash
   refactor: Rewrite order method
   ```
* Если добавить переменные для поставщика задач `TASK_SYSTEM_NAME` и `TASK_SYSTEM_LINK`.

   ```bash
   refactor: Rewrite order method [Заявка SomeTaskSystemName](https://some-task-system.ru/tasks/view?code=IU000000)
   ```


## Пример результата

```md
# История изменений

## [ [1.1.0](https://gitlab.some.ru/your.repo.ru//-/tags/1.1.0) ] - 01.01.2023
- Реализовано:
  - Установлена версия РНР до 8.2
  - Добавлен пакет Redis
- Изменено:
  - Переписан метод получения заказа
```

## Завершение работы

* Когда скрипт закончит работу и файл будет заполнен, он предложит создать коммит
* Если нажать Y то коммит создастся и ветка зальется в репозиторий, а локально удалится
* Если N, действия эти нужно будет делать вручную
* Далее останется только перейти в репозиторий и создав MR перенести изменения на develop
* А потом также через MR перенести изменения на master

## Лицензия

ChangeLog Writer выпущен под лицензией MIT. Подробную информацию можно найти в файле LICENSE в корневой директории проекта.