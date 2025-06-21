from setuptools import setup, find_packages

setup(
    name="tree2json",
    version="0.1.0",
    packages=find_packages(),
    author="knighthood2001",
    description="将项目目录树字符串转换为JSON结构",
    long_description=open("README.md", encoding="utf-8").read(),
    long_description_content_type="text/markdown",
    python_requires=">=3.6",
    classifiers=[
        "Programming Language :: Python :: 3",
        "License :: OSI Approved :: MIT License",
    ],
)
