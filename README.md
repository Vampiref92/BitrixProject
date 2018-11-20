# BitrixProject
Настройки для битрикс проекта

## Что содержит
Содержит базовые настройки .gitignore в файле project_gitignore

Содержит базовый композер и php_cs_fixer

Базовую структуру классов через композер на папку src

Настройки проекта через init.php

Подключение симфониевого роутинга

Оптимизацию контента на событиях вывода буфера


## Git

### fast merge
```
MERGE_BRANCH="dev" && CURRENT_BRANCH="$(git symbolic-ref --short HEAD)" && git checkout ${MERGE_BRANCH} && git pull origin ${MERGE_BRANCH} && git pull origin ${CURRENT_BRANCH}  && git push origin ${MERGE_BRANCH} && git checkout ${CURRENT_BRANCH}
```

### git alias
```
git config --global alias.co checkout
git config --global alias.ci commit
git config --global alias.st status
git config --global alias.br branch
git config --global alias.nb 'checkout -b'
git config --global alias.hist 'log --pretty=format:"%h %ad | %s%d [%an]" --graph --date=short'
git config --global alias.curb 'symbolic-ref --short HEAD'
git config --global alias.cblo '!bn=`git curb`; git branch -u origin/$bn'
```

### fast commit and push
```
git config --global alias.fcp '!bn=`git curb`; git fc; git push origin $bn'
git config --global alias.fc '!bn=`git curb`; read -p "Введите сообщение: " msg; git add .; git ci -m "[$bn] $msg"'
```

### fast merge
```
git config --global alias.fmb '!branch="$1"; unset 1; cur_branch=`git curb`; if [ ! -n "$branch" ]; then read -p "Введите название ветки для мерджа: " branch; fi; git co $branch; git pull origin $branch; git pull origin $cur_branch; git push origin $branch; git co $cur_branch'

### fast merge to dev
```
git config --global alias.fmbd '!git fmb "dev"'

### fast merge to master
```
git config --global alias.fmbm '!git fmb "master"'
```